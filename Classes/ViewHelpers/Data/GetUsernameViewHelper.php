<?php

namespace Slub\SlubFindExtend\ViewHelpers\Data;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * GetUsernameViewHelper
 */
class GetUsernameViewHelper extends AbstractViewHelper
{
    /**
     * @param mixed[] $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @throws AspectNotFoundException
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $context = GeneralUtility::makeInstance(Context::class);
        return (string)$context->getPropertyFromAspect('frontend.user', 'username');
    }
}
