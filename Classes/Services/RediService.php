<?php

namespace Slub\SlubFindExtend\Services;

/**
 * Class RedisService
 */
class RediService
{
    public function __construct(private readonly \TYPO3\CMS\Core\Cache\CacheManager $cacheManager)
    {
    }

    /**
     * @param array<string, mixed> $document
     * @param array<string, mixed>|null $enriched
     * @return array<string, mixed>|null
     */
    public function getCached(array $document, ?array $enriched): ?array
    {
        $cache = $this->cacheManager->getCache('resolv_link_electronic');
        $cacheIdentifier = hash('sha256', (string)$document['id']);
        $entry = $cache->get($cacheIdentifier);
        if (!is_array($entry)) {
            // Try to resolve article against redi
            $entry = $this->getElectronicHoldingFromData($document, $enriched);
            $cache->set($cacheIdentifier, $entry);
        }

        return $entry;
    }

    /**
     * Tries to resolve Article against holdings
     *
     * @param array<string, mixed> $document
     * @param array<string, mixed>|null $enriched
     * @return array<string, mixed>|null
     */
    private function getElectronicHoldingFromData(array $document, ?array $enriched): ?array
    {
        $status = [];

        if (!is_array($enriched) || !isset($enriched['fields']) || !is_array($enriched['fields'])) {
            return null;
        }

        $fields = $enriched['fields'];

        $article = (string)($fields['rft.atitle'] ?? '');
        $firstISSN = (string)($fields['rft.issn'][0] ?? '');
        $volume = (string)($fields['rft.volume'] ?? '');
        $spage = (string)($fields['rft.spage'] ?? '');
        $epage = (string)($fields['rft.epage'] ?? '');
        $pages = (string)($fields['rft.pages'] ?? '');
        $issue = (string)($fields['rft.issue'] ?? '');
        $genre = (string)($fields['rft.genre'] ?? '');
        $date = (string)($fields['rft.date'] ?? '');
        $language = (string)($fields['languages'][0] ?? '');
        $doi = (string)($fields['doi'] ?? '');
        $jtitle = (string)($fields['rft.jtitle'] ?? '');
        $firstAuthor = (array)($fields['authors'][0] ?? []);
        $firstAuthorAulast = (string)($firstAuthor['rft.aulast'] ?? '');
        $firstAuthorAufirst = (string)($firstAuthor['rft.aufirst'] ?? '');

        $url = 'http://www-s.redi-bw.de/links/?rl_site=slub&atitle=' . urlencode($article) .
            '&issn=' . urlencode($firstISSN) .
            '&volume=' . urlencode($volume) .
            '&spage=' . urlencode($spage) .
            '&epage=' . urlencode($epage) .
            '&pages=' . urlencode($pages) .
            '&issue=' . urlencode($issue) .
            '&aulast=' . urlencode($firstAuthorAulast) .
            '&aufirst=' . urlencode($firstAuthorAufirst) .
            '&genre=' . urlencode($genre) .
            '&sid=katalogbeta.slub-dresden.de&date=' . urlencode($date) .
            '&language=' . urlencode($language) .
            '&doi=' . urlencode($doi) .
            '&title=' . urlencode($jtitle);

        $html = $this->getData($url);
        if ($html === '') {
            return null;
        }

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        @$doc->loadHTML($html);

        $xpath = new \DOMXPath($doc);

        $infolink = $this->getNodeValue($xpath, "//span[contains(@class,'t_infolink')]/a/@href");
        $access = $this->getNodeValue($xpath, "//div[@id ='t_ezb']/div/p/b");
        $doilink = $this->getNodeValue($xpath, "//dd[contains(@class,'doi_d')]/span/a/@href");

        $status_code = 10;
        $url = '';
        $via = '';
        $oa_via = '';
        $oa_more = '';

        $links = [];

        $ezbNodes = $xpath->query("//div[@id ='t_ezb']/div/div[contains(@class,'t_ezb_result')]/p");
        $resultCount = $ezbNodes instanceof \DOMNodeList ? $ezbNodes->length : 0;

        for ($i = 0; $i < $resultCount; $i++) {
            $link = [];

            $ezb_status_code = 10;

            $ezb_status = $this->getNodeValue($xpath, "//div[@id ='t_ezb']/div/div[contains(@class,'t_ezb_result')]/p/span[contains(@class, 't_ezb_yellow') or contains(@class, 't_ezb_green') or contains(@class, 't_ezb_red')]/@class", $i);
            $ezb_status_via = trim($this->getNodeValue($xpath, "//div[@id ='t_ezb']/div/div[contains(@class,'t_ezb_result')]/p", $i));
            $ezb_url = $this->getNodeValue($xpath, "//div[@id ='t_ezb']/div/div[contains(@class,'t_ezb_result')]/p/span[contains(@class,'t_link')]/a/@href", $i);

            $viaPosition = strpos($ezb_status_via, 'via');
            $ezb_via = $viaPosition === false ? '' : substr($ezb_status_via, $viaPosition + 4, -4);

            switch ($ezb_status) {
                case 't_ezb_green':
                    $ezb_status_code = 0;
                    break;
                case 't_ezb_yellow':
                    $ezb_status_code = 2;
                    break;
                case 't_ezb_red':
                    $ezb_status_code = 4;
                    break;
            }

            if ($ezb_status_code < $status_code) {
                $link['status'] = $ezb_status_code;
                $link['via'] = $ezb_via;
                $link['url'] = $ezb_url;
            }

            $links[] = $link;
        }

        $oa_url = $this->getNodeValue($xpath, "//div[@id ='t_oadoi']/div/div[contains(@class,'t_ezb_result')]/p/span[contains(@class,'t_link')]/a/@href");

        if (strlen($oa_url) > 0) {
            $oa_via = trim($this->getNodeValue($xpath, "//div[@id ='t_oadoi']/div/div[contains(@class,'t_ezb_result')]/p"));
            preg_match('/\(via (.*?), (.*)\)/', $oa_via, $output_array);
            $oa_via = $output_array[1] ?? '';
            $oa_more = $output_array[2] ?? '';
        }

        $status['infolink'] = $infolink;
        $status['access'] = $access === 'freigeschaltet' ? 1 : 0;
        $status['links'] = $links;
        $status['oa_url'] = $oa_url;
        $status['oa_via'] = $oa_via;
        $status['oa_more'] = $oa_more;
        $status['doilink'] = $doilink;

        return $status;
    }

    private function getData(string $url): string
    {
        $ch = curl_init();
        $timeout = 10;
        if ($url === '') {
            return '';
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($ch);
        curl_close($ch);
        return is_string($data) ? $data : '';
    }

    private function getNodeValue(\DOMXPath $xpath, string $expression, int $index = 0): string
    {
        $nodes = $xpath->query($expression);
        if (!$nodes instanceof \DOMNodeList) {
            return '';
        }

        $node = $nodes->item($index);
        if ($node === null) {
            return '';
        }

        return $node->nodeValue ?? '';
    }
}
