<?php

namespace Slub\SlubFindExtend\Utility;

/**
 * Loads the extension-local vendor autoloader without touching TYPO3 bootstrap state.
 */
final class LocalVendorAutoloader
{
    /**
     * @var bool
     */
    private static $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $autoloadFile = dirname(__DIR__, 2) . '/vendor/autoload.php';

        if (!is_file($autoloadFile)) {
            throw new \RuntimeException('Missing slub_find_extend vendor autoloader: ' . $autoloadFile, 1716386250);
        }

        require_once $autoloadFile;
        self::$loaded = true;
    }
}
