<?php

namespace Slub\SlubFindExtend\Services;

use File_MARC_Reference;

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('slub_find_extend') . 'vendor/autoload.php');

/**
 * Class MarcRefrenceResolverService
 */
class MarcRefrenceResolverService
{
    /**
     * Resiolves a reference against raw data
     *
     * @param string $path
     * @param object $record
     * @param bool $index
     * @return array|bool
     */
    public function resolveReference($path, $record, $index = null)
    {
        if (!$record instanceof \File_MARC_Record) {
            return false;
        }

        $reference = new File_MARC_Reference($path, $record);
        $content = $reference->content;

        if ($index !== null && is_array($content)) {
            return $content[$index];
        }
        return $reference;
    }
}
