<?php

namespace Slub\SlubFindExtend\ViewHelpers\Data;

/**
 * View Helper to return merge facets with active facets
 *
 * Usage examples are available in Private/Partials/Test.html.
 */

use Solarium\QueryType\Select\Result\Facet\Field;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class MergeWithActiveFacetsViewHelper extends AbstractViewHelper
{
    /**
     * Register arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('data', 'array', 'The data to test', false, null);
        $this->registerArgument('key', 'string', 'The data to test', false, null);
        $this->registerArgument('activeFacets', 'array', 'The data to test', false, null);
    }

    /**
     * @return array
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        /** @var Field $data */
        $data = $arguments['data'];

        if (is_array($arguments['activeFacets']) && is_array($arguments['activeFacets'][$arguments['key']]) && count($arguments['activeFacets'][$arguments['key']])) {
            $mergedData = ['values' => $data->getValues()];

            foreach ($arguments['activeFacets'][$arguments['key']] as $activeKey => $activeValue) {
                if (empty($mergedData['values'][$activeKey])) {
                    $mergedData['values'] = array_merge([$activeKey => $activeValue], $mergedData['values']);
                }
            }

            return $mergedData;
        }
        return $data;
    }
}
