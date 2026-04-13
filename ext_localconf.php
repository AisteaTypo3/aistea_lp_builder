<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {
    ExtensionManagementUtility::addTypoScript(
        'aistea_lp_builder',
        'setup',
        "@import 'EXT:aistea_lp_builder/Configuration/TypoScript/setup.typoscript'"
    );

    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['aisteaLpBuilderSlides']
        = \Aistea\LpBuilder\Eid\SlidesEid::class . '::main';
})();
