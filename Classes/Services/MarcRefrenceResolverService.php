<?php

namespace Slub\SlubFindExtend\Services;

use File_MARC_Reference;
use Slub\SlubFindExtend\Utility\LocalVendorAutoloader;

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
     * @param int|null $index
     * @return \File_MARC_Reference|array<mixed>|false
     */
    public function resolveReference(string $path, object $record, ?int $index = null): \File_MARC_Reference|array|false
    {
        LocalVendorAutoloader::load();

        if (!$record instanceof \File_MARC_Record) {
            return false;
        }

        $reference = new File_MARC_Reference($path, $record);
        $content = $reference->content;

        if ($index !== null) {
            return $content[$index] ?? false;
        }
        return $reference;
    }
}
