<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {
    ExtensionManagementUtility::addStaticFile(
        'aistea_lp_builder',
        'Configuration/TypoScript',
        'LP Builder'
    );
})();
