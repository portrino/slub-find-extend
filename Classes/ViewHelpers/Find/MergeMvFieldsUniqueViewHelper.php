<?php

namespace Slub\SlubFindExtend\ViewHelpers\Find;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class MergeMvFieldsUniqueViewHelper extends AbstractViewHelper
{
    /**
     * Register arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('document', 'array', 'The document object', true);
        $this->registerArgument('fields', 'string', 'The fields to merge, comma-eperated', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $allUrls = [];

        $fields = explode(',', $arguments['fields']);

        foreach ($fields as $field) {
            $field = trim($field);
            if (isset($arguments['document'][$field])) {
                $allUrls = array_merge($arguments['document'][$field], $allUrls);
            }
        }

        return array_unique($allUrls);
    }
}
