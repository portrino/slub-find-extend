<?php

namespace Slub\SlubFindExtend\Services;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * (c) 2013 http://stackoverflow.com/users/1696923/fazzyx
 *
 * From http://stackoverflow.com/questions/17440847/typo3-extbase-set-and-get-values-from-session
 *
 * Class SessionHandlerService
 */
class SessionHandlerService implements SingletonInterface
{
    private string $prefixKey = 'slub_find_extend_';

    /**
     * Returns the object stored in the user´s PHP session
     * @return mixed the stored object
     */
    public function restoreFromSession(string $key): mixed
    {
        $sessionData = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user')->getKey('ses', $this->prefixKey . $key);
        return unserialize((string)$sessionData, ['allowed_classes' => true]);
    }

    /**
     * Writes an object into the PHP session
     * @param mixed $object any serializable object to store into the session
     */
    public function writeToSession(mixed $object, string $key): SessionHandlerService
    {
        $sessionData = serialize($object);
        $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user')->setKey('ses', $this->prefixKey . $key, $sessionData);
        $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user')->storeSessionData();
        return $this;
    }

    /**
     * Cleans up the session: removes the stored object from the PHP session
     */
    public function cleanUpSession(string $key): SessionHandlerService
    {
        $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user')->setKey('ses', $this->prefixKey . $key, null);
        $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user')->storeSessionData();
        return $this;
    }

    public function setPrefixKey(string $prefixKey): void
    {
        $this->prefixKey = $prefixKey;
    }
}
