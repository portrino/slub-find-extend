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
use Slub\SlubFindExtend\Backend\Solr\SearchHandler;
use Slub\SlubFindExtend\Services\StopWordService;
use Solarium\QueryType\Select\Query\Query;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Slot implementation before the
 *
 * @category    Slots
 */
class AdvancedQuery
{
    protected StopWordService $stopWordService;

    /**
     * Contains the settings of the current extension
     *
     * @var array<string, mixed>
     * @api
     */
    protected array $settings;

    /**
     * @var ConfigurationManagerInterface
     */
    protected ConfigurationManagerInterface $configurationManager;

    /**
     * @param StopWordService|null $stopWordService
     * @param ConfigurationManagerInterface|null $configurationManager
     */
    public function __construct(?StopWordService $stopWordService = null, ?ConfigurationManagerInterface $configurationManager = null)
    {
        $this->stopWordService = $stopWordService ?? GeneralUtility::makeInstance(StopWordService::class);
        $this->configurationManager = $configurationManager ?? GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $this->settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'Find', 'Find');
    }

    /**
     * Special handling for pure numeric queries
     *
     * @param string $parameter
     */
    private function handleNumeric(string $parameter): string
    {
        if (is_numeric($parameter)) {
            $parameter = sprintf($this->settings['queryModifier']['numeric'], $parameter);
        }

        return $parameter;
    }

    /**
     * @param string $originalQuerystring
     * @param SearchHandler $searchHandler
     * @param array<string, mixed> $settings
     * @return string
     */
    public function handlePhraseMatch(string $originalQuerystring, SearchHandler $searchHandler, array $settings): string
    {
        if (preg_match('/^".*"$/', trim($originalQuerystring)) === 1) {
            return '';
        }

        $boost = ($settings['queryModifier']['phraseMatchBoost'] ?? '') !== '' ? '^' . $settings['queryModifier']['phraseMatchBoost'] : '';

        return ' OR ' . $searchHandler->createAdvancedQueryString('"' . $originalQuerystring . '"') . $boost;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function handleIsilMatch(string $originalQuerystring, SearchHandler $searchHandler, array $settings): string
    {
        if (preg_match('/^".*"$/', trim($originalQuerystring)) === 1) {
            return '';
        }

        if (($settings['queryModifier']['isilQueryString'] ?? '') === '') {
            return '';
        }

        $originalQuerystring = trim($originalQuerystring, $settings['queryModifier']['isilQueryTrim']);
        $originalQuerystring = str_replace([' ', ')', '('], ['\\\ ', '\\\)', '\\\('], $originalQuerystring);

        $return = ' OR ' . sprintf($this->settings['queryModifier']['isilQueryString'], $originalQuerystring);

        return $return;
    }

    /**
     * @param array<string, mixed> $settings Settings Array
     */
    private function handleStripIntFields(array &$settings, string $queryParameter): void
    {
        if (!is_numeric(substr($queryParameter, 0, 2))) {
            foreach ($settings['DismaxFields'] as $key => $value) {
                if ((int)$key >= 800) {
                    unset($settings['DismaxFields'][$key], $queryParameter);
                }
            }
        }
    }

    /**
     * strip chars that breaks the solr query
     *
     * @param string $queryParameter Settings Array
     */
    private function stripCharsFromQuery(string $queryParameter): string
    {
        return str_replace(['/', '\\'], [' '], $queryParameter);
    }

    /**
     * Clean Query parameters the solr query
     *
     * @param string $queryParameter Settings Array
     */
    private function cleanParameter(string $queryParameter): string
    {
        return str_replace([':', '?', ';', '-', '!', '&', '–', '(', ')', '+', '=', '$', '[', ']', '.', '„', '“', '‘', '’'], ' ', $queryParameter);
    }

    /**
     * Slot to build the advanced query
     *
     * @param Query &$query
     * @param array<string, mixed> $arguments request arguments
     */
    public function build(Query &$query, array $arguments): void
    {
        $defaultQuery = $arguments['q']['default'] ?? '';
        $queryParameter = trim((string) (is_array($defaultQuery) ? ($defaultQuery[0] ?? '') : $defaultQuery));
        $originalQueryParameter = $queryParameter;

        $settings = $this->settings['components'];

        if ($settings !== []) {
            if (strlen($queryParameter) > 0) {
                if (isset($this->settings['queryModifier'])) {
                    if (!(bool)($this->settings['queryModifier']['phraseMatch'] ?? false)) {
                        $queryParameter = $this->stripCharsFromQuery($queryParameter);
                    }

                    if ((bool)($this->settings['queryModifier']['cleanParameter'] ?? false)) {
                        $queryParameter = $this->cleanParameter($queryParameter);
                    }

                    if ((bool)($this->settings['queryModifier']['stopwords'] ?? false)) {
                        $queryParameter = $this->stopWordService->cleanQueryString($queryParameter);
                    }

                    if (($this->settings['queryModifier']['numeric'] ?? '') !== '') {
                        $queryParameter = $this->handleNumeric($queryParameter);
                    }
                }

                if ((bool)($this->settings['queryModifier']['stripIntFields'] ?? false)) {
                    $this->handleStripIntFields($settings, $queryParameter);
                }

                $searchHandler = new SearchHandler($settings);

                $querystring = $searchHandler->createAdvancedQueryString($queryParameter);

                if ((bool)($this->settings['queryModifier']['phraseMatch'] ?? false)) {
                    $querystring .= $this->handlePhraseMatch($originalQueryParameter, $searchHandler, $this->settings);
                }

                if ((bool)($this->settings['queryModifier']['isilMatch'] ?? false)) {
                    $querystring .= $this->handleIsilMatch($originalQueryParameter, $searchHandler, $this->settings);
                }

                $query->setQuery($querystring);
            } else {
                if (($settings['DismaxHandler'] ?? '') === 'edismax') {
                    $dismax = $query->getEDisMax();
                } else {
                    $dismax = $query->getDisMax();
                }

                if (isset($settings['DismaxParams']) && is_array($settings['DismaxParams']) && $settings['DismaxParams'] !== []) {
                    foreach ($settings['DismaxParams'] as $params) {
                        if ($params['name'] === 'bf') {
                            $dismax->setBoostFunctions($params['value']);
                        }
                        if ($params['name'] === 'bq') {
                            $dismax->setBoostQuery($params['value']);
                        }
                    }
                }
            }
        }
    }
}
