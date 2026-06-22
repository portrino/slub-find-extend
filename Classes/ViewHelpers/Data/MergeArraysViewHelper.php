<?php

namespace Slub\SlubFindExtend\ViewHelpers\Data;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class MergeArraysViewHelper extends AbstractViewHelper
{
    /**
     * Register arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('arrayOne', 'array', 'The first array.', true, []);
        $this->registerArgument('arrayTwo', 'array', 'The second array', true, []);
    }

    /**
     * @return mixed
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $arrayOne = $arguments['arrayOne'];
        $arrayTwo = $arguments['arrayTwo'];

        if ($arrayOne !== null && $arrayTwo !== null) {
            return array_merge($arrayOne, $arrayTwo);
        }

        if ($arrayOne === null) {
            return $arrayTwo;
        }
        if ($arrayTwo === null) {
            return $arrayOne;
        }

        return null;
    }
}
