<?php

namespace Slub\SlubFindExtend\Slots;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Log\LoggerAwareTrait;
use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Solarium\Core\Client\Response;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result;
use Symfony\Component\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Slot implementation before the
 *
 * @category    Slots
 */
class EnrichSolrResult implements \Psr\Log\LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(?ConfigurationManagerInterface $configurationManager = null)
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $this->configurationManager = $configurationManager ?? GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $this->settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'Find', 'Find');
    }

    /**
     * Contains the settings of the current extension
     *
     * @var array<string, mixed>
     * @api
     */
    protected array $settings;

    /**
     * Contains data to be logged on error
     *
     * @var string
     * @api
     */
    protected string $logData = '';

    /**
     * @var ConfigurationManagerInterface
     */
    protected ConfigurationManagerInterface $configurationManager;

    /**
     * Slot to enrich finds detail view
     *
     * @param array<string, mixed> &$assignments
     */
    public function detail(array &$assignments): void
    {
        $assignments['enriched'] = ['fields' => []];
        $enriched = [];

        $document = $assignments['document'];
        /* @var $document Document */

        if ($document instanceof Document) {
            $fields = $document->getFields();
            $pageType = (int)($GLOBALS['TYPO3_REQUEST']->getParsedBody()['type'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['type'] ?? null);

            if (isset($this->settings['enrich']['detail']) && is_array($this->settings['enrich']['detail']) && $this->settings['enrich']['detail'] !== []) {
                foreach ($this->settings['enrich']['detail'] as $enrichment) {
                    $filter_passed = false;

                    if (isset($enrichment['filter_field']) && isset($enrichment['filter_pattern'])) {
                        $filter_fields = is_array($fields[$enrichment['filter_field']]) ? $fields[$enrichment['filter_field']] : [$fields[$enrichment['filter_field']]];

                        foreach ($filter_fields as $filter_field) {
                            if (preg_match($enrichment['filter_pattern'], $filter_field, ) === 1) {
                                $filter_passed = true;
                                break;
                            }
                        }
                    } else {
                        $filter_passed = true;
                    }

                    if ($filter_passed) {
                        $field_data = '';
                        $user_data = (string)($GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user')->user['username'] ?? '');

                        $check_fields = is_array($fields[$enrichment['check_field']]) ? $fields[$enrichment['check_field']] : [$fields[$enrichment['check_field']]];

                        $check_typenum = false;

                        if (array_key_exists('check_typenum', $enrichment) && $pageType !== (int)$enrichment['check_typenum']) {
                            $check_typenum = true;
                        }

                        foreach ($check_fields as $check_field) {
                            if (preg_match($enrichment['check_pattern'], $check_field, $matches) === 1) {
                                $field_data = $matches[1];
                            }
                        }

                        if (strlen($field_data) > 0 && !$check_typenum) {
                            $this->logData = $fields['id'] . ': ' . sprintf($enrichment['ws'], $field_data, $user_data);

                            try {
                                $enriched = $this->getSafeData(sprintf($enrichment['ws'], $field_data, $user_data));
                            } catch (\Exception $e) {
                                $assignments['enriched']['error'] = [
                                    'code' => $e->getMessage(),
                                    'host' => parse_url($enrichment['ws'], PHP_URL_HOST),
                                    'host_hash' => hash('sha256', (string)(parse_url($enrichment['ws'], PHP_URL_HOST) ?? '')),
                                ];
                            }
                            if ($enriched !== []) {
                                $assignments['enriched']['fields'] = array_merge($assignments['enriched']['fields'], $enriched);

                                foreach ($assignments['enriched']['fields'] as $key => $value) {
                                    $normalizedKey = str_replace(' ', '', (string)$key);
                                    if ((string)$key !== $normalizedKey) {
                                        $assignments['enriched']['fields'][$normalizedKey] = $assignments['enriched']['fields'][$key];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $assignments['show_detaildata'] = $_COOKIE['show_detaildata'];
    }

    /**
     * Slot to enrich finds detail view
     *
     * @param Result|array<string, mixed>|null &$resultSet
     * @param-out Result|array<string, mixed>|null $resultSet
     */
    public function index(mixed &$resultSet): void
    {
        if (!$resultSet instanceof Result || !isset($this->settings['enrich']['index'])) {
            return;
        }

        $documents = $resultSet->getDocuments();

        if ($documents !== []) {
            if (is_array($this->settings['enrich']['index'])) {
                foreach ($this->settings['enrich']['index'] as $enrichment) {
                    if (isset($enrichment['check_field']) && isset($enrichment['type']) && isset($enrichment['filter_field'])) {
                        $field_passed = false;
                        foreach ($documents as $document) {
                            if (array_key_exists($enrichment['check_field'], $document->getFields())) {
                                $field_passed = true;
                                break;
                            }
                        }

                        if ($field_passed) {
                            $values = [];
                            foreach ($documents as $document) {
                                $fieldValue = $document->getFields()[$enrichment['check_field']] ?? null;
                                if ($fieldValue !== null && $fieldValue !== '' && $fieldValue !== []) {
                                    if (is_array($fieldValue)) {
                                        $values = array_merge($values, $fieldValue);
                                    } else {
                                        $values[] = $fieldValue;
                                    }
                                }
                            }

                            $values = array_values(array_filter(array_unique($values), static fn (mixed $value): bool => $value !== null && $value !== ''));

                            if ($values !== []) {
                                $results = [];
                                switch ($enrichment['type']) {
                                    case 'solr':
                                        $results = $this->solrEnrich($documents, $values, $enrichment);
                                        break;
                                    default:
                                        break;
                                }

                                if ($results !== []) {
                                    $body = json_decode($resultSet->getResponse()->getBody(), true);
                                    foreach ($body['response']['docs'] as &$document) {
                                        if (($document[$enrichment['check_field']] ?? null) !== null && ($document[$enrichment['check_field']] ?? null) !== '') {
                                            foreach ($results as $item) {
                                                if ($this->checkForIntersection($document[$enrichment['check_field']], $item->getFields()[$enrichment['filter_field']])) {
                                                    if (!isset($document['enriched']) || is_array($document['enriched'])) {
                                                        $document['enriched'][]['fields'] = $item->getFields();
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    // rewire the resultSet
                                    $encodedBody = json_encode($body);
                                    if (!is_string($encodedBody)) {
                                        continue;
                                    }
                                    $response = new Response($encodedBody, $resultSet->getResponse()->getHeaders());
                                    $resultSet = new \Solarium\QueryType\Select\Result\Result($resultSet->getQuery(), $response);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * A safe way to get data from a webservice
     * @param string $url
     * @return array<int|string, mixed>
     */
    private function getSafeData(string $url): array
    {
        return (array)$this->safe_json_decode($this->getData($url));
    }

    /**
     * A safe way to decode stringified json data
     * @param array<mixed>|string $value
     * @return array<int|string, mixed>|string|null
     */
    private function safe_json_decode(array|string $value): array|string|null
    {
        if (is_array($value)) {
            return null;
        }
        if ($value === '') {
            return '';
        }

        $original_value = $value;

        $decoded = json_decode($value, true);

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $decoded;
            case JSON_ERROR_UTF8:
                $this->logger->error('JSON_ERROR_UTF8: ' . $this->logData);
                $clean = $this->unutf8ize($value);
                return $this->safe_json_decode($clean);
            case JSON_ERROR_SYNTAX:
                $this->logger->error('JSON_ERROR_SYNTAX: ' . $this->logData);
                // Fix double ,, syntax error
                if (strpos($original_value, ',,') !== false) {
                    $value = str_replace(',,', ',', $original_value);
                    $decoded = json_decode($value, true);
                    return $decoded;
                }
                throw new \Exception('LO-JD', 1949037822);
            case JSON_ERROR_CTRL_CHAR:
                $this->logger->error('JSON_ERROR_CTRL_CHAR: ' . $this->logData);
                // Fix tab syntax error
                $fixed = 0;
                if (strpos($original_value, "\t") !== false) {
                    $value = str_replace("\t", '', $original_value);
                    $decoded = json_decode($value, true);
                    $fixed = 1;
                }
                if (strpos($original_value, "\n") !== false) {
                    $value = str_replace("\n", '', $value);
                    $decoded = json_decode($value, true);
                    $fixed = 1;
                }
                if (strpos($original_value, "\r") !== false) {
                    $value = str_replace("\r", '', $value);
                    $decoded = json_decode($value, true);
                    $fixed = 1;
                }
                if ($fixed === 1) {
                    return $decoded;
                }
                throw new \Exception('LO-JD', 8172653598);

            default:
                $this->logger->error('JSON_UNKNOWN_ERROR: ' . $this->logData);
                throw new \Exception('LO-JD', 2951117843);
        }
    }

    /**
     * Decode UTF8 recursively
     * @param mixed $mixed
     * @return array<mixed>|string
     */
    private function unutf8ize(mixed $mixed): array|string
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->unutf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, 'ISO-8859-1', 'UTF-8');
        }
        return $mixed;
    }

    private function getData(string $url): string
    {
        $ch = curl_init();
        $timeout = 10;
        if (isset($this->settings['enrich']['timeout']) && (int)$this->settings['enrich']['timeout'] > 0) {
            $timeout = (int)$this->settings['enrich']['timeout'];
        }
        if ($url === '') {
            return '';
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $data = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch) > 0) {
            $error = 'Curl error: ' . curl_error($ch);
            $this->logger->warning($error);
            throw new \Exception('LO-AC', 1506332584);
        }
        if ($http_code !== 200) {
            $error = 'Curl error: ' . $http_code . ': ' . $url;
            $this->logger->warning($error);
            throw new \Exception('LO-AC', 9531831929);
        }

        curl_close($ch);
        return is_string($data) ? $data : '';
    }

    /**
     * @param array<int, \Solarium\Core\Query\DocumentInterface> $documents
     * @param array<int, string> $values
     * @param array<string, mixed> $enrichment
     * @return array<int, \Solarium\Core\Query\DocumentInterface>
     */
    private function solrEnrich(array $documents, array $values, array $enrichment): array
    {
        $connectionSettings = $this->settings['connections'][$this->settings['activeConnection']]['options'];

        $results = [];
        $config = [
            'endpoint' => [
                'localhost' => [
                    'host' => (isset($enrichment['endpoint']) && isset($enrichment['endpoint']['host'])) ? $enrichment['endpoint']['host'] : $connectionSettings['host'],
                    'port' => (isset($enrichment['endpoint']) && isset($enrichment['endpoint']['port'])) ? (int)($enrichment['endpoint']['port']) : (int)($connectionSettings['port']),
                    'path' => (isset($enrichment['endpoint']) && isset($enrichment['endpoint']['path'])) ? $enrichment['endpoint']['path'] : $connectionSettings['path'],
                    'timeout' => (isset($enrichment['endpoint']) && isset($enrichment['endpoint']['timeout'])) ? $enrichment['endpoint']['timeout'] : $connectionSettings['timeout'],
                    'scheme' => (isset($enrichment['endpoint']) && isset($enrichment['endpoint']['scheme'])) ? $enrichment['endpoint']['scheme'] : $connectionSettings['scheme'],
                    'core' => (isset($enrichment['endpoint']) && isset($enrichment['endpoint']['core'])) ? $enrichment['endpoint']['core'] : $connectionSettings['core'],
                ],
            ],
        ];
        $solr = new Client(new Curl(), new EventDispatcher(), $config);

        if (($enrichment['filter_field'] ?? null) === 'id') {
            $query = new \Solarium\QueryType\RealtimeGet\Query();
            $query->addIds($values);
            $query->setResponseWriter('json');

            try {
                $response = $solr->realtimeGet($query);
                $results = $response->getDocuments();
            } catch (\Exception $e) {
                $this->logger->error('Error while querying Solr: ' . $e);
            }
        }

        return $results;
    }

    /**
     * Test for variable intersection
     * @param mixed $data1
     * @param mixed $data2
     * @return bool
     */
    private function checkForIntersection(mixed $data1, mixed $data2): bool
    {
        if ($data1 === null || $data1 === '' || $data1 === [] || $data2 === null || $data2 === '' || $data2 === []) {
            return false;
        }

        if (is_array($data1) && is_array($data2)) {
            if (count(array_intersect($data1, $data2)) > 0) {
                return true;
            }
        } elseif (is_array($data1) xor is_array($data2)) {
            if (is_array($data1)) {
                return in_array($data2, $data1, true);
            }
            return is_array($data2) && in_array($data1, $data2, true);
        } else {
            return $data1 === $data2;
        }
        return false;
    }
}
