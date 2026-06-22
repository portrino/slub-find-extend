<?php

namespace Slub\SlubFindExtend\ViewHelpers\Data;

use File_MARC_Reference;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('slub_find_extend') . 'vendor/autoload.php');

/**
 * GetMarcDataViewHelper
 */
class GetMarcDataViewHelper extends AbstractViewHelper
{
    /**
     * Register arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('record', 'mixed', 'The decoded MARC record', false, null);
        $this->registerArgument('path', 'string', 'The MARC path', false, null);
        $this->registerArgument('index', 'integer', 'If return data might be an array, define which index should be returned', false, null);
    }

    /**
     * @return mixed
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        if ($arguments['record']) {
            $reference = new File_MARC_Reference((string)$arguments['path'], $arguments['record']);
            $content = $reference->content;

            if ($arguments['index'] !== null && is_array($content)) {
                return $content[$arguments['index']];
            }
            return $content;
        }

        return null;
    }
}
