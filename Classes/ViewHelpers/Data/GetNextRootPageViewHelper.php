<?php

namespace Slub\SlubFindExtend\ViewHelpers\Data;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * GetRootPageViewHelper
 */
class GetNextRootPageViewHelper extends AbstractViewHelper
{
    /**
     * @return array
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $rootline = [];

        $request = null;
        if ($renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $renderingContext->getAttribute(ServerRequestInterface::class);
        }

        if ($request instanceof ServerRequestInterface) {
            $pageInformation = $request->getAttribute('frontend.page.information');

            if ($pageInformation instanceof PageInformation) {
                $rootline = $pageInformation->getRootLine();
                $rootline = array_reverse($rootline);
            }
        }

        foreach ($rootline as $page) {
            if ($page['is_siteroot'] === 1) {
                return $page;
            }
        }

        return [];
    }
}
