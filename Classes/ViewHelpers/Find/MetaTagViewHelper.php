<?php

namespace Slub\SlubFindExtend\ViewHelpers\Find;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class MetaTagViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('property', 'string', 'meta tag property');
        $this->registerArgument('content', 'string', 'meta tag content');
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): void {
        if (empty($arguments['property'])) {
            return;
        }

        if (!empty($arguments['content'])) {
            $content = $arguments['content'];
        } else {
            $content = $renderChildrenClosure();
        }

        $metaTag = '<meta property="' . $arguments['property'] . '" content="' . $content . '">';

        $GLOBALS['TSFE']->additionalHeaderData[$arguments['property']] = $metaTag;

        return;
    }
}
