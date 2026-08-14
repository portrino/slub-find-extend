<?php

namespace Slub\SlubFindExtend\Services;

/**
 * Class StatusService
 */
class LinksFromMarcFullrecordService
{
    protected MarcRefrenceResolverService $marcRefrenceResolverService;

    public function __construct(MarcRefrenceResolverService $marcRefrenceResolverService)
    {
        $this->marcRefrenceResolverService = $marcRefrenceResolverService;
    }

    /**
     * Returns the links from the MARC fullrecord
     *
     * @param object $fullrecord
     * @param list<string>|null $isil
     * @param bool $unique
     * @param bool $merged
     * @return array<string, int|list<array{uri: string, note: string, material: string, prefix: string}>>|list<array{uri: string, note: string, material: string, prefix: string}>
     */
    public function getLinks(object $fullrecord, ?array $isil = null, bool $unique = false, bool $merged = false): array
    {
        $defaultPrefix = 'https://wwwdb.dbod.de/login?url=';
        $noPrefixHosts = ['wwwdb.dbod.de', 'www.dbod.de', 'nbn-resolving.de', 'digital.slub-dresden.de', 'digital.zlb.de', 'www.deutschefotothek.de', 'mediathek.slub-dresden.de', 'ezb.ur.de', 'dbis.uni-regensburg.de', 'www.bibliothek.uni-regensburg.de'];
        $blacklistLabel = ['Kostenfrei', 'Volltext'];

        $resourceLinks = [];
        $relatedLinks = [];
        $isilLinks = [];
        $unspecificLinks = [];

        $reference = $this->marcRefrenceResolverService->resolveReference('856', $fullrecord);
        if (!$reference instanceof \File_MARC_Reference || !isset($reference->cache['856']) || !is_array($reference->cache['856'])) {
            return [
                'isil' => $isilLinks,
                'resource' => $resourceLinks,
                'related' => $relatedLinks,
                'count' => 0,
            ];
        }

        for ($i = 0; $i < count($reference->cache['856']); $i++) {
            $prefix = $defaultPrefix;
            $note = '';
            $material = '';
            $ind1 = $reference->cache['856[' . $i . ']']->getIndicator(1);
            $ind2 = $reference->cache['856[' . $i . ']']->getIndicator(2);

            if ($reference->cache['856[' . $i . ']']->getSubfield('u')) {
                $uri = trim($reference->cache['856[' . $i . ']']->getSubfield('u')->getData());

                if (substr($uri, 0, 4) === 'urn:') {
                    $uri = 'http://nbn-resolving.de/' . $uri;
                }
                $uri = str_replace('https://wwwdb.dbod.de/login?url=', '', $uri);

                $uriParsed = parse_url($uri);

                if (is_array($uriParsed) && isset($uriParsed['host']) && in_array($uriParsed['host'], $noPrefixHosts, true)) {
                    $prefix =  '';
                }

                if ($reference->cache['856[' . $i . ']']->getSubfield('z')) {
                    $note = $reference->cache['856[' . $i . ']']->getSubfield('z')->getData();
                    if (in_array($note, $blacklistLabel, true)) {
                        $note = '';
                    }
                }
                if ($reference->cache['856[' . $i . ']']->getSubfield('3')) {
                    $material = $reference->cache['856[' . $i . ']']->getSubfield('3')->getData();
                    $material = str_replace('#', ' - ', $material);
                    if (in_array($material, $blacklistLabel, true)) {
                        $material = '';
                    }
                }

                if ($reference->cache['856[' . $i . ']']->getSubfield('9') && $isil !== null && in_array($reference->cache['856[' . $i . ']']->getSubfield('9')->getData(), $isil, true)) {
                    if ($reference->cache['856[' . $i . ']']->getSubfield('9')->getData() === 'LFER') {
                        $prefix =  '';
                    }

                    $linkNotInArray = true;
                    if ($unique) {
                        $linkNotInArray = array_search($uri, array_column($isilLinks, 'uri'), true) === false;
                    }

                    if ($linkNotInArray) {
                        $isilLinks[] = ['uri' => $uri, 'note' => $note, 'material' => $material, 'prefix' => $prefix];
                    }
                } elseif (($ind1 === '4') && ($ind2 === '2')) {
                    $linkNotInArray = true;
                    if ($unique) {
                        $linkNotInArray = array_search($uri, array_column($relatedLinks, 'uri'), true) === false;
                    }

                    if ($linkNotInArray) {
                        $relatedLinks[] = ['uri' => $uri, 'note' => $note, 'material' => $material, 'prefix' => ''];
                    }
                } elseif (($ind1 === '4') && ($ind2 === '0')) {
                    $linkNotInArray = true;
                    if ($unique) {
                        $linkNotInArray = array_search($uri, array_column($resourceLinks, 'uri'), true) === false;
                    }

                    if ($linkNotInArray) {
                        $resourceLinks[] = ['uri' => $uri, 'note' => $note, 'material' => $material, 'prefix' => $prefix];
                    }
                } else {
                    $linkNotInArray = true;
                    if ($unique) {
                        $linkNotInArray = array_search($uri, array_column($unspecificLinks, 'uri'), true) === false;
                    }

                    if ($linkNotInArray) {
                        $unspecificLinks[] = ['uri' => $uri, 'note' => $note, 'material' => $material, 'prefix' => $prefix];
                    }
                }
            }
        }

        if (count($isilLinks) === 0 && count($relatedLinks) === 0 && count($resourceLinks) === 0 && count($unspecificLinks) > 0) {
            $resourceLinks = $unspecificLinks;
        }

        if ($merged) {
            return array_merge($isilLinks, $resourceLinks, $relatedLinks);
        }
        return [
            'isil' => $isilLinks,
            'resource' => $resourceLinks,
            'related' => $relatedLinks,
            'count' => count($isilLinks) + count($resourceLinks) + count($relatedLinks),
        ];
    }
}
