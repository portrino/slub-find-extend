<?php

namespace Slub\SlubFindExtend\Services;

/**
 * Class StatusService
 */
class LinksFromAiFullrecordService
{
    /**
     * Returns the links from the AI fullrecord
     *
     * @param object $fullrecord
     * @param string $isil
     * @param bool $resolve
     * @return array<int, mixed>
     */
    public function getLinks(object $fullrecord, string $isil = '', bool $resolve = false): array
    {
        return [];
    }
}
