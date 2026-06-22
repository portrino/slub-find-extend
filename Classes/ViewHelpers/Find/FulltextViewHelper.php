<?php

namespace Slub\SlubFindExtend\ViewHelpers\Find;

use Slub\SlubFindExtend\Services\FulltextService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class FulltextViewHelper extends AbstractViewHelper
{
    /**
     * @var FulltextService
     */
    protected static $fulltextService;

    /**
     * Registers own arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('document', '\Solarium\QueryType\Select\Result\Document', 'Result document', true);
    }

    /**
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        return static::getFulltextService()->getFulltextLink($arguments['document']);
    }

    private static function getFulltextService()
    {
        if (static::$fulltextService === null) {
            static::$fulltextService = GeneralUtility::makeInstance(FulltextService::class);
        }

        return static::$fulltextService;
    }
}
