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
    /**
     * @var StopWordService
     */
    protected $stopWordService;

    /**
     * Contains the settings of the current extension
     *
     * @var array
     * @api
     */
    protected $settings;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

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
     * @param $parameter
     */
    private function handleNumeric($parameter)
    {
        if (is_numeric($parameter)) {
            $parameter = sprintf($this->settings['queryModifier']['numeric'], $parameter);
        }

        return $parameter;
    }

    /**
     * @param string $querystring
     * @param string $originalQuerystring
     * @param SearchHandler $searchHandler
     * @param array $settings
     * @return string
     */
    public function handlePhraseMatch($originalQuerystring, $searchHandler, $settings)
    {
        if (preg_match('/^".*"$/', trim($originalQuerystring))) {
            return '';
        }

        $boost = ($settings['queryModifier']['phraseMatchBoost']) ? '^' . $settings['queryModifier']['phraseMatchBoost'] : '';

        return ' OR ' . $searchHandler->createAdvancedQueryString('"' . $originalQuerystring . '"') . $boost;
    }

    public function handleIsilMatch($originalQuerystring, $searchHandler, $settings)
    {
        if (preg_match('/^".*"$/', trim($originalQuerystring))) {
            return '';
        }

        if (!$settings['queryModifier']['isilQueryString']) {
            return '';
        }

        $originalQuerystring = trim($originalQuerystring, $settings['queryModifier']['isilQueryTrim']);
        $originalQuerystring = str_replace([' ', ')', '('], ['\\\ ', '\\\)', '\\\('], $originalQuerystring);

        $return = ' OR ' . sprintf($this->settings['queryModifier']['isilQueryString'], $originalQuerystring);

        return $return;
    }

    /**
     * @param array $settings Settings Array
     */
    private function handleStripIntFields(&$settings, $queryParameter): void
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
    private function stripCharsFromQuery($queryParameter)
    {
        return str_replace(['/', '\\'], [' '], $queryParameter);
    }

    /**
     * Clean Query parameters the solr query
     *
     * @param string $queryParameter Settings Array
     */
    private function cleanParameter($queryParameter)
    {
        return str_replace([':', '?', ';', '-', '!', '&', '–', '(', ')', '+', '=', '$', '[', ']', '.', '„', '“', '‘', '’'], ' ', $queryParameter);
    }

    /**
     * Slot to build the advanced query
     *
     * @param Query &$query
     * @param array $arguments request arguments
     */
    public function build(&$query, $arguments): void
    {
        $queryParameter = trim(is_array($arguments['q']['default']) ? $arguments['q']['default'][0] : $arguments['q']['default']);
        $originalQueryParameter = $queryParameter;

        $settings = $this->settings['components'];

        if ($settings) {
            if (strlen($queryParameter) > 0) {
                if (isset($this->settings['queryModifier'])) {
                    if (!isset($this->settings['queryModifier']['phraseMatch']) || !$this->settings['queryModifier']['phraseMatch']) {
                        $queryParameter = $this->stripCharsFromQuery($queryParameter);
                    }

                    if (isset($this->settings['queryModifier']['cleanParameter']) && $this->settings['queryModifier']['cleanParameter']) {
                        $queryParameter = $this->cleanParameter($queryParameter);
                    }

                    if (isset($this->settings['queryModifier']['stopwords']) && $this->settings['queryModifier']['stopwords']) {
                        $queryParameter = $this->stopWordService->cleanQueryString($queryParameter);
                    }

                    if (isset($this->settings['queryModifier']['numeric']) && $this->settings['queryModifier']['numeric']) {
                        $queryParameter = $this->handleNumeric($queryParameter);
                    }
                }

                if (isset($this->settings['queryModifier']['stripIntFields']) && $this->settings['queryModifier']['stripIntFields']) {
                    $this->handleStripIntFields($settings, $queryParameter);
                }

                $searchHandler = new SearchHandler($settings);

                $boostquery = $searchHandler->createBoostQueryString($queryParameter);

                $querystring = $searchHandler->createAdvancedQueryString($queryParameter);

                if (isset($this->settings['queryModifier']['phraseMatch']) && $this->settings['queryModifier']['phraseMatch']) {
                    $querystring .= $this->handlePhraseMatch($originalQueryParameter, $searchHandler, $this->settings);
                }

                if (isset($this->settings['queryModifier']['isilMatch']) && $this->settings['queryModifier']['isilMatch']) {
                    $querystring .= $this->handleIsilMatch($originalQueryParameter, $searchHandler, $this->settings);
                }

                $query->setQuery($querystring);
            } else {
                if (isset($settings['DismaxHandler']) && $settings['DismaxHandler'] === 'edismax') {
                    $dismax = $query->getEDisMax();
                } else {
                    $dismax = $query->getDisMax();
                }

                if (isset($settings['DismaxParams']) && $settings['DismaxParams']) {
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
