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
use Solarium\QueryType\Select\Result\Document;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Slot implementation before the
 *
 * @category    Slots
 */
class ModifySolrResult
{
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

    public function __construct(?ConfigurationManagerInterface $configurationManager = null)
    {
        $this->configurationManager = $configurationManager ?? GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $this->settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'Find', 'Find');
    }

    /**
     * Slot to decode data from Solr result to use in templates
     *
     * @param array &$assignments
     */
    public function decode(&$assignments): void
    {
        $document = $assignments['document'];
        /* @var $document Document */

        if ($document && $this->settings['decode']) {
            $fields = $document->getFields();

            foreach ($this->settings['decode'] as $decoding) {
                switch ($decoding['type']) {
                    case 'marc':
                        if ($fields['recordtype'] === 'marc') {
                            $decoder = new \Slub\SlubFindExtend\Slots\Decoder\Marc21();
                            $assignments['decoded'][$decoding['field']] = $decoder->decode($fields[$decoding['field']]);
                        }
                        break;
                    case 'marcfinc':
                        if ($fields['recordtype'] === 'marcfinc') {
                            $decoder = new \Slub\SlubFindExtend\Slots\Decoder\Marc21();
                            $assignments['decoded'][$decoding['field']] = $decoder->decode($fields[$decoding['field']]);
                        }
                        break;
                    case 'ai':
                        if (($fields['recordtype'] === 'ai')
                            && (strrpos($fields[$decoding['field']], 'blob:', -strlen($fields[$decoding['field']])) === false)) {
                            $assignments['enriched']['fields'] = (array)json_decode($fields[$decoding['field']]);
                        }
                        break;
                    case 'is':
                        if ($fields['recordtype'] === 'is') {
                            $assignments['enriched']['fields'] = (array)json_decode($fields[$decoding['field']]);
                        }
                        break;
                }
            }
        }
    }

    /**
     * Slot to filter data from Solr result against blacklist values
     *
     * @param array &$assignments
     */
    public function blacklist(&$assignments): void
    {
        $document = $assignments['document'];

        if ($document && $this->settings['blacklist']) {
            $fields = $document->getFields();

            foreach ($this->settings['blacklist'] as $blacklistName => $blacklistValues) {
                if (isset($fields[$blacklistName]) && is_array($fields[$blacklistName]) && is_array($blacklistValues)) {
                    $fields[$blacklistName] = preg_grep('/^(' . str_replace('/', '\/', implode('|', $blacklistValues)) . ')$/', $fields[$blacklistName], PREG_GREP_INVERT);
                    $fields[$blacklistName] = array_values($fields[$blacklistName]);
                }
            }

            $assignments['document'] = new \Solarium\QueryType\Select\Result\Document($fields);
        }
    }

    /**
     * Slot to enrich finds detail view
     *
     * @param array &$resultSet
     */
    public function index(&$resultSet)
    {
    }
}
