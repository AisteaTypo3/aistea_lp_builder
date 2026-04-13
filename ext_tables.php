<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {
    ExtensionManagementUtility::addPageTSConfig(
        "@import 'EXT:aistea_lp_builder/Configuration/page.tsconfig'"
    );
})();
