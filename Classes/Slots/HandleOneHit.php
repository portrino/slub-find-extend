<?php

namespace Slub\SlubFindExtend\Slots;

use Solarium\QueryType\Select\Result\Document;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * Slot implementation before the
 *
 * @category    Slots
 */
class HandleOneHit
{
    public const IDFIELDS = ['record_id', 'barcode', 'rsn', 'isbn', 'ismn', 'issn', 'zdb', 'signatur', 'title', 'title_full', 'kxp_id_str', 'swb_id_str', 'finc_id_str'];

    /**
     * Contains the settings of the current extension
     *
     * @var array
     * @api
     */
    protected $settings;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    public function __construct(?ConfigurationManagerInterface $configurationManager = null, ?UriBuilder $uriBuilder = null)
    {
        $this->configurationManager = $configurationManager ?? GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $this->uriBuilder = $uriBuilder ?? GeneralUtility::makeInstance(UriBuilder::class);
        $this->settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'Find', 'Find');
    }

    /**
     * Slot to handle one hit results
     *
     * @param array &$resultSet
     */
    public function index(&$resultSet): void
    {
        $idhit = false;
        if (isset($this->settings['handleOnHit']) && $this->settings['handleOnHit'] == '0') {
            return;
        }

        if (
            $resultSet
            && ($resultSet->getNumFound() === 1)
            && ((is_array($_GET['tx_find_find']['facet'])) && (count($_GET['tx_find_find']['facet']) === 0))
            && (!$_GET['type'] > 0)
        ) {
            /* @var $document Document */
            $document = $resultSet->getDocuments()[0];
            foreach ($resultSet->getHighlighting()->getResult($document['id'])->getFields() as $key => $value) {
                if (in_array($key, $this::IDFIELDS)) {
                    $idhit = true;
                }
            }

            if ($idhit) {
                $uri = $this->uriBuilder->uriFor('detail', ['id' => $document['id'], 'underlyingQuery' => ['q' => $_GET['tx_find_find']['q'], 'position' => 1]], 'Search', 'find', 'Find');

                header('Location: ' . $uri, true, 302);
                die();
            }
        }
    }
}
