<?php

namespace Slub\SlubFindExtend\ViewHelpers\Find;

use Slub\SlubFindExtend\Services\LinksFromAiFullrecordService;
use Slub\SlubFindExtend\Services\LinksFromMarcFullrecordService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class GetLinksViewHelper extends AbstractViewHelper
{
    /**
     * @var LinksFromMarcFullrecordService
     */
    protected static $linksFromMarcFullrecordService;

    /**
     * @var LinksFromAiFullrecordService
     */
    protected static $linksFromAiFullrecordService;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('document', 'object', 'The index document', true);
        $this->registerArgument('fullrecord', 'object', 'The raw fullrecord ', false, null);
        $this->registerArgument('isil', 'array', 'If you want to filter the data by isil', false, null);
        $this->registerArgument('index', 'boolean', 'Is this a call from an index overview?', false, false);
        $this->registerArgument('unique', 'boolean', 'Should only unique Links be outputted?', false, false);
        $this->registerArgument('merged', 'boolean', 'Should links be returned in one array?', false, false);
    }

    /**
     * @return array
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        if (($arguments['document']['recordtype'] === 'ai' || $arguments['document']['recordtype'] === 'is') && (!$arguments['index'])) {
            return static::getLinksFromAiFullrecordService()->getLinks($arguments['fullrecord'], $arguments['isil'], true);
        }
        switch ($arguments['document']['recordtype']) {
            case 'marc':
            case 'marcfinc':
                return static::getLinksFromMarcFullrecordService()->getLinks($arguments['fullrecord'], $arguments['isil'], $arguments['unique'], $arguments['merged']);
            case 'ai':
            case 'is':
                return static::getLinksFromAiFullrecordService()->getLinks($arguments['fullrecord'], $arguments['isil'], false);
            default:
                return [];
        }
    }

    private static function getLinksFromMarcFullrecordService()
    {
        if (static::$linksFromMarcFullrecordService === null) {
            static::$linksFromMarcFullrecordService = GeneralUtility::makeInstance(LinksFromMarcFullrecordService::class);
        }

        return static::$linksFromMarcFullrecordService;
    }

    private static function getLinksFromAiFullrecordService()
    {
        if (static::$linksFromAiFullrecordService === null) {
            static::$linksFromAiFullrecordService = GeneralUtility::makeInstance(LinksFromAiFullrecordService::class);
        }

        return static::$linksFromAiFullrecordService;
    }
}
