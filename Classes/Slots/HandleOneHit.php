<?php

namespace Slub\SlubFindExtend\Slots;

use Psr\Http\Message\ResponseFactoryInterface;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result;
use TYPO3\CMS\Core\Http\PropagateResponseException;
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
     * @var array<string, mixed>
     * @api
     */
    protected array $settings;

    /**
     * @var UriBuilder
     */
    protected UriBuilder $uriBuilder;

    /**
     * @var ConfigurationManagerInterface
     */
    protected ConfigurationManagerInterface $configurationManager;

    public function __construct(?ConfigurationManagerInterface $configurationManager = null, ?UriBuilder $uriBuilder = null)
    {
        $this->configurationManager = $configurationManager ?? GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $this->uriBuilder = $uriBuilder ?? GeneralUtility::makeInstance(UriBuilder::class);
        $this->settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'Find', 'Find');
    }

    /**
     * Slot to handle one hit results
     *
     * @param Result|mixed $resultSet
     */
    public function index(mixed &$resultSet): void
    {
        $idhit = false;
        if (($this->settings['handleOnHit'] ?? '') === '0') {
            return;
        }

        $request = $GLOBALS['TYPO3_REQUEST'];
        $queryParams = $request->getQueryParams();
        $findArguments = is_array($queryParams['tx_find_find'] ?? null) ? $queryParams['tx_find_find'] : [];
        $facetArguments = is_array($findArguments['facet'] ?? null) ? $findArguments['facet'] : [];
        $pageType = (int)($queryParams['type'] ?? 0);

        if (
            $resultSet instanceof Result
            && $resultSet->getNumFound() === 1
            && count($facetArguments) === 0
            && $pageType <= 0
        ) {
            /* @var $document Document */
            $document = $resultSet->getDocuments()[0];
            $documentId = (string)($document->getFields()['id'] ?? '');
            if ($documentId === '') {
                return;
            }

            foreach ($resultSet->getHighlighting()->getResult($documentId)->getFields() as $key => $value) {
                if (in_array($key, self::IDFIELDS, true)) {
                    $idhit = true;
                }
            }

            if ($idhit) {
                $uri = $this->uriBuilder->uriFor('detail', ['id' => $documentId, 'underlyingQuery' => ['q' => $findArguments['q'] ?? [], 'position' => 1]], 'Search', 'find', 'Find');
                $response = GeneralUtility::makeInstance(ResponseFactoryInterface::class)
                    ->createResponse(302)
                    ->withHeader('Location', $uri);
                throw new PropagateResponseException($response, 1755241149);
            }
        }
    }
}
