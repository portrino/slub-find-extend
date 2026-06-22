<?php

namespace Slub\SlubFindExtend\Slots;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use Slub\SlubFindExtend\Services\SessionHandlerService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Slot implementation before the
 *
 * @category    Slots
 */
class ModifyArguments
{
    /**
     * Contains the settings of the current extension
     *
     * @var array
     * @api
     */
    protected $settings;

    /**
     * @var SessionHandlerService
     */
    protected $sessionHandler;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    public function __construct(?ConfigurationManagerInterface $configurationManager = null, ?SessionHandlerService $sessionHandler = null)
    {
        $this->configurationManager = $configurationManager ?? GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $this->sessionHandler = $sessionHandler ?? GeneralUtility::makeInstance(SessionHandlerService::class);
        $this->settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'Find', 'Find');
    }

    /**
     * Slot to modify request arguments
     *
     * @param array &$assignments
     */
    public function modify(&$arguments): void
    {
        $id = $arguments['id'] ?? '';
        if (!is_scalar($id) || (string)$id === '') {
            return;
        }
        $id = (string)$id;
        $underlyingQuery = $arguments['underlyingQuery'] ?? null;

        if (is_array($underlyingQuery) && count($underlyingQuery) > 0) {
            $this->sessionHandler->writeToSession($underlyingQuery, $id . '_underlyingQuery');
        } else {
            $storedUnderlyingQuery = $this->sessionHandler->restoreFromSession($id . '_underlyingQuery');
            if ($storedUnderlyingQuery) {
                $arguments['underlyingQuery'] = $storedUnderlyingQuery;
            }
        }
    }
}
