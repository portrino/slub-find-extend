<?php

namespace Slub\SlubFindExtend\Services;

use Solarium\QueryType\Select\Result\Document;

/**
 * Class FulltextService
 */
class FulltextService
{
    public const RESOLVER_BASE = '//wwwdb.dbod.de/login?url=%s';

    /**
     * @param Document $document
     * @param \File_MARC_Record $record NULL
     * @return bool|string
     */
    public function getFulltextLink(Document $document, ?\File_MARC_Record $record = null): bool|string
    {
        if (in_array('Online', $document['facet_avail'], true) && strlen($document['url'][0]) > 0) {
            return sprintf(self::RESOLVER_BASE, $document['url'][0]);
        }
        if (($document['format'][0] === 'Electronic Resource (Remote Access)') && (strlen($document['url'][0]) > 0)) {
            return sprintf(self::RESOLVER_BASE, $document['url'][0]);
        }

        return false;
    }
}
