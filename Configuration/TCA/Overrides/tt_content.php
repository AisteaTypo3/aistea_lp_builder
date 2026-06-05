<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function (): void {
    $contentType = 'aistea_lp_product_slider';
    $horizontalContentType = 'aistea_lp_horizontal_slider';
    $imageSequenceContentType = 'aistea_lp_image_sequence';
    $fullScreenVideoContentType = 'aistea_lp_fullscreen_video';
    $beforeAfterContentType = 'aistea_lp_before_after';
    $hotspotImageContentType = 'aistea_lp_hotspot_image';
    $interviewBoxesContentType = 'aistea_lp_interview_boxes';
    $highlightBoxesContentType = 'aistea_lp_highlight_boxes';
    $heroSequenzContentType = 'aistea_hero_sequenz';
    $builderGroup = 'aistea';

    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        $builderGroup,
        'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:group.aistea',
        'after:special'
    );

    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.CType.aistea_lp_product_slider',
            'value' => $contentType,
            'icon' => 'aistea-lp-builder-ce',
            'group' => $builderGroup,
        ]
    );
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.CType.aistea_lp_fullscreen_video',
            'value' => $fullScreenVideoContentType,
            'icon' => 'aistea-lp-fullscreen-video-ce',
            'group' => $builderGroup,
        ]
    );
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.CType.aistea_lp_horizontal_slider',
            'value' => $horizontalContentType,
            'icon' => 'aistea-lp-horizontal-slider-ce',
            'group' => $builderGroup,
        ]
    );
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.CType.aistea_lp_image_sequence',
            'value' => $imageSequenceContentType,
            'icon' => 'aistea-lp-image-sequence-ce',
            'group' => $builderGroup,
        ]
    );
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.CType.aistea_lp_before_after',
            'value' => $beforeAfterContentType,
            'icon' => 'aistea-lp-before-after-ce',
            'group' => $builderGroup,
        ]
    );
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.CType.aistea_lp_hotspot_image',
            'value' => $hotspotImageContentType,
            'icon' => 'aistea-lp-hotspot-image-ce',
            'group' => $builderGroup,
        ]
    );
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.CType.aistea_lp_interview_boxes',
            'value' => $interviewBoxesContentType,
            'icon' => 'aistea-lp-interview-boxes-ce',
            'group' => $builderGroup,
        ]
    );
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.CType.aistea_lp_highlight_boxes',
            'value' => $highlightBoxesContentType,
            'icon' => 'aistea-lp-highlight-boxes-ce',
            'group' => $builderGroup,
        ]
    );
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.CType.aistea_hero_sequenz',
            'value' => $heroSequenzContentType,
            'icon' => 'content-aistea-hero-sequenz',
            'group' => $builderGroup,
        ]
    );

    $newColumns = [
        'tx_aistealpproductslider_layout_mode' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_layout_mode',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'default',
                'items' => [
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:layout_mode.default', 'default'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:layout_mode.compact', 'compact'],
                ],
            ],
        ],
        'tx_aistealpproductslider_breakpoint_mobile' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_breakpoint_mobile',
            'config' => [
                'type' => 'number',
                'format' => 'integer',
                'default' => 768,
                'range' => [
                    'lower' => 320,
                    'upper' => 1920,
                ],
            ],
        ],
        'tx_aistealpproductslider_reduced_motion_behavior' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_reduced_motion_behavior',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'static',
                'items' => [
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:reduced_motion.static', 'static'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:reduced_motion.allow_manual', 'allowManualPlay'],
                ],
            ],
        ],
        'tx_aistealpproductslider_video_autoplay_desktop' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_video_autoplay_desktop',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ],
        'tx_aistealpproductslider_video_autoplay_mobile' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_video_autoplay_mobile',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'tx_aistealpproductslider_preload_strategy' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_preload_strategy',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'smart',
                'items' => [
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:preload.none', 'none'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:preload.smart', 'smart'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:preload.aggressive', 'aggressive'],
                ],
            ],
        ],
        'tx_aistealpproductslider_stage_aspect_ratio' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_stage_aspect_ratio',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'default' => '1/1',
                'eval' => 'trim',
                'placeholder' => '16/9',
            ],
        ],
        'tx_aistealpproductslider_theme' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_theme',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'dark',
                'items' => [
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:theme.dark', 'dark'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:theme.light', 'light'],
                ],
            ],
        ],
        'tx_aistealpproductslider_slides' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_slides',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_aistealpproductslider_slide',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'foreign_sortby' => 'sorting',
                'appearance' => [
                    'expandSingle' => true,
                    'useSortable' => true,
                    'enabledControls' => [
                        'info' => true,
                        'new' => true,
                        'dragdrop' => true,
                        'sort' => true,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ],
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'minitems' => 0,
            ],
        ],
        'tx_aistealpproductslider_hslides' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_hslides',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_aistealpproductslider_hslide',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'foreign_sortby' => 'sorting',
                'appearance' => [
                    'expandSingle' => true,
                    'useSortable' => true,
                    'enabledControls' => [
                        'info' => true,
                        'new' => true,
                        'dragdrop' => true,
                        'sort' => true,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ],
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'minitems' => 0,
            ],
        ],
        'tx_aistealpproductslider_sequence_frames' => [
            'exclude' => true,
            'displayCond' => 'FIELD:tx_aistealpproductslider_sequence_source:=:filelist',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_sequence_frames',
            'config' => [
                'type' => 'file',
                'allowed' => 'jpg,jpeg,png,webp,avif',
                'maxitems' => 300,
                'minitems' => 1,
            ],
        ],
        'tx_aistealpproductslider_sequence_source' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_sequence_source',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'filelist',
                'items' => [
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:sequence_source.filelist', 'filelist'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:sequence_source.collection', 'collection'],
                ],
            ],
        ],
        'tx_aistealpproductslider_sequence_collection' => [
            'exclude' => true,
            'displayCond' => 'FIELD:tx_aistealpproductslider_sequence_source:=:collection',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_sequence_collection',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'sys_file_collection',
                'foreign_table_where' => ' AND {#sys_file_collection}.{#deleted}=0',
                'default' => 0,
            ],
        ],
        'tx_aistealpproductslider_sequence_fps' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_sequence_fps',
            'config' => [
                'type' => 'number',
                'format' => 'integer',
                'default' => 12,
                'range' => [
                    'lower' => 1,
                    'upper' => 60,
                ],
            ],
        ],
        'tx_aistealpproductslider_sequence_loop' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_sequence_loop',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'tx_aistealpproductslider_fsv_short_video' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_fsv_short_video',
            'config' => [
                'type' => 'file',
                'allowed' => 'mp4',
                'maxitems' => 1,
                'minitems' => 1,
            ],
        ],
        'tx_aistealpproductslider_fsv_long_video' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_fsv_long_video',
            'config' => [
                'type' => 'file',
                'allowed' => 'vimeo',
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'tx_aistealpproductslider_fsv_kicker' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_fsv_kicker',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ],
        ],
        'tx_aistealpproductslider_fsv_headline' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_fsv_headline',
            'config' => [
                'type' => 'input',
                'size' => 60,
                'eval' => 'trim',
            ],
        ],
        'tx_aistealpproductslider_fsv_button_label' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_fsv_button_label',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
                'default' => 'Watch full video',
            ],
        ],

        // Before/After Slider
        'tx_aistealpproductslider_ba_image_before' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_ba_image_before',
            'config' => [
                'type' => 'file',
                'allowed' => 'jpg,jpeg,png,webp,avif',
                'maxitems' => 1,
                'minitems' => 1,
            ],
        ],
        'tx_aistealpproductslider_ba_image_after' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_ba_image_after',
            'config' => [
                'type' => 'file',
                'allowed' => 'jpg,jpeg,png,webp,avif',
                'maxitems' => 1,
                'minitems' => 1,
            ],
        ],
        'tx_aistealpproductslider_ba_label_before' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_ba_label_before',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'tx_aistealpproductslider_ba_label_after' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_ba_label_after',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'tx_aistealpproductslider_ba_initial_position' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_ba_initial_position',
            'config' => [
                'type' => 'number',
                'format' => 'integer',
                'default' => 50,
                'range' => [
                    'lower' => 0,
                    'upper' => 100,
                ],
            ],
        ],

        // Hotspot Image
        'tx_aistealpproductslider_hi_image' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_hi_image',
            'config' => [
                'type' => 'file',
                'allowed' => 'jpg,jpeg,png,webp,avif',
                'maxitems' => 1,
                'minitems' => 1,
            ],
        ],
        'tx_aistealpproductslider_hi_hotspots' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_hi_hotspots',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_aistealpproductslider_hotspot',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'foreign_sortby' => 'sorting',
                'appearance' => [
                    'expandSingle' => true,
                    'useSortable' => true,
                    'enabledControls' => [
                        'info' => true,
                        'new' => true,
                        'dragdrop' => true,
                        'sort' => true,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ],
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'minitems' => 0,
            ],
        ],
        'tx_aistealpproductslider_interviews' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_interviews',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_aistealpproductslider_interview',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'foreign_sortby' => 'sorting',
                'appearance' => [
                    'expandSingle' => true,
                    'useSortable' => true,
                    'enabledControls' => [
                        'info' => true,
                        'new' => true,
                        'dragdrop' => true,
                        'sort' => true,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ],
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'minitems' => 1,
                'maxitems' => 4,
            ],
        ],
        'tx_aistealpproductslider_highlights' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.tx_aistealpproductslider_highlights',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_aistealpproductslider_highlight',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'foreign_sortby' => 'sorting',
                'appearance' => [
                    'expandSingle' => true,
                    'useSortable' => true,
                    'enabledControls' => [
                        'info' => true,
                        'new' => true,
                        'dragdrop' => true,
                        'sort' => true,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ],
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'minitems' => 1,
                'maxitems' => 4,
            ],
        ],
        'file_collection' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.file_collection',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_file_collection',
                'foreign_table_where' => ' AND {#sys_file_collection}.{#type}=\'folder\'',
                'default' => 0,
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'desktop_fps' => [
            'exclude' => true,
            'displayCond' => 'FIELD:scroll_driven:=:0',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.desktop_fps',
            'config' => [
                'type' => 'number',
                'default' => 24,
                'range' => [
                    'lower' => 1,
                    'upper' => 120,
                ],
            ],
        ],
        'desktop_loop' => [
            'exclude' => true,
            'displayCond' => 'FIELD:scroll_driven:=:0',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.desktop_loop',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'mobile_breakpoint' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.mobile_breakpoint',
            'config' => [
                'type' => 'number',
                'default' => 768,
                'range' => [
                    'lower' => 0,
                    'upper' => 3840,
                ],
            ],
        ],
        'mobile_mode' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.mobile_mode',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.mobile_mode.lastFrame', 'lastFrame'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.mobile_mode.firstFrame', 'firstFrame'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.mobile_mode.staticImage', 'staticImage'],
                ],
                'default' => 'lastFrame',
            ],
        ],
        'mobile_static_image' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.mobile_static_image',
            'displayCond' => 'FIELD:mobile_mode:=:staticImage',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'preload_strategy' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.preload_strategy',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.preload_strategy.smart', 'smart'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.preload_strategy.all', 'all'],
                ],
                'default' => 'smart',
            ],
        ],
        'scroll_driven' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.scroll_driven',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'alt_text' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.alt_text',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'max' => 255,
                'default' => '',
            ],
        ],
        'background_color' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.background_color',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'max' => 32,
                'default' => '',
                'placeholder' => '#000000',
            ],
        ],
        'render_full_list_on_mobile' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tt_content.render_full_list_on_mobile',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
    ];

    ExtensionManagementUtility::addTCAcolumns('tt_content', $newColumns);

    $showItem = '
        --palette--;;general,
        header,
        tx_aistealpproductslider_layout_mode,
        tx_aistealpproductslider_theme,
        tx_aistealpproductslider_stage_aspect_ratio,
        tx_aistealpproductslider_breakpoint_mobile,
        tx_aistealpproductslider_reduced_motion_behavior,
        tx_aistealpproductslider_video_autoplay_desktop,
        tx_aistealpproductslider_video_autoplay_mobile,
        tx_aistealpproductslider_preload_strategy,
        --div--;LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tabs.slides,
        tx_aistealpproductslider_slides,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;hidden,
        --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
        rowDescription
    ';

    $GLOBALS['TCA']['tt_content']['types'][$contentType] = [
        'showitem' => $showItem,
        'previewRenderer' => \Aistea\LpBuilder\Backend\ContentElementPreviewRenderer::class,
        'columnsOverrides' => [
            'header' => [
                'config' => [
                    'placeholder' => 'Product Viewer',
                ],
            ],
        ],
    ];

    $horizontalShowItem = '
        --palette--;;general,
        header,
        --div--;LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tabs.slides,
        tx_aistealpproductslider_hslides,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;hidden,
        --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
        rowDescription
    ';

    $GLOBALS['TCA']['tt_content']['types'][$horizontalContentType] = [
        'showitem' => $horizontalShowItem,
        'previewRenderer' => \Aistea\LpBuilder\Backend\ContentElementPreviewRenderer::class,
        'columnsOverrides' => [
            'header' => [
                'config' => [
                    'placeholder' => 'Product Story',
                ],
            ],
        ],
    ];

    $imageSequenceShowItem = '
        --palette--;;general,
        header,
        tx_aistealpproductslider_sequence_source,
        tx_aistealpproductslider_sequence_collection,
        tx_aistealpproductslider_sequence_frames,
        tx_aistealpproductslider_sequence_fps,
        tx_aistealpproductslider_sequence_loop,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;hidden,
        --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
        rowDescription
    ';

    $GLOBALS['TCA']['tt_content']['types'][$imageSequenceContentType] = [
        'showitem' => $imageSequenceShowItem,
        'previewRenderer' => \Aistea\LpBuilder\Backend\ContentElementPreviewRenderer::class,
        'columnsOverrides' => [
            'header' => [
                'config' => [
                    'placeholder' => 'Image Sequence',
                ],
            ],
        ],
    ];

    $fullScreenVideoShowItem = '
        --palette--;;general,
        header,
        tx_aistealpproductslider_fsv_short_video,
        tx_aistealpproductslider_fsv_long_video,
        tx_aistealpproductslider_fsv_kicker,
        tx_aistealpproductslider_fsv_headline,
        tx_aistealpproductslider_fsv_button_label,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;hidden,
        --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
        rowDescription
    ';

    $GLOBALS['TCA']['tt_content']['types'][$fullScreenVideoContentType] = [
        'showitem' => $fullScreenVideoShowItem,
        'previewRenderer' => \Aistea\LpBuilder\Backend\ContentElementPreviewRenderer::class,
        'columnsOverrides' => [
            'header' => [
                'config' => [
                    'placeholder' => 'Immersive video block',
                ],
            ],
        ],
    ];

    $beforeAfterShowItem = '
        --palette--;;general,
        header,
        tx_aistealpproductslider_ba_image_before,
        tx_aistealpproductslider_ba_image_after,
        --palette--;;ba_labels,
        tx_aistealpproductslider_ba_initial_position,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;hidden,
        --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
        rowDescription
    ';

    $GLOBALS['TCA']['tt_content']['types'][$beforeAfterContentType] = [
        'showitem' => $beforeAfterShowItem,
        'previewRenderer' => \Aistea\LpBuilder\Backend\ContentElementPreviewRenderer::class,
        'columnsOverrides' => [
            'header' => [
                'config' => [
                    'placeholder' => 'Before / After Comparison',
                ],
            ],
        ],
    ];

    $GLOBALS['TCA']['tt_content']['palettes']['ba_labels'] = [
        'showitem' => 'tx_aistealpproductslider_ba_label_before, tx_aistealpproductslider_ba_label_after',
    ];

    $hotspotImageShowItem = '
        --palette--;;general,
        header,
        tx_aistealpproductslider_hi_image,
        --div--;LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tabs.hotspots,
        tx_aistealpproductslider_hi_hotspots,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;hidden,
        --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
        rowDescription
    ';

    $GLOBALS['TCA']['tt_content']['types'][$hotspotImageContentType] = [
        'showitem' => $hotspotImageShowItem,
        'previewRenderer' => \Aistea\LpBuilder\Backend\ContentElementPreviewRenderer::class,
        'columnsOverrides' => [
            'header' => [
                'config' => [
                    'placeholder' => 'Hotspot Image',
                ],
            ],
        ],
    ];

    $interviewBoxesShowItem = '
        --palette--;;general,
        --palette--;;headers,
        --div--;LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tabs.interviews,
        tx_aistealpproductslider_interviews,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;hidden,
        --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
        rowDescription
    ';

    $GLOBALS['TCA']['tt_content']['types'][$interviewBoxesContentType] = [
        'showitem' => $interviewBoxesShowItem,
        'previewRenderer' => \Aistea\LpBuilder\Backend\ContentElementPreviewRenderer::class,
        'columnsOverrides' => [
            'header' => [
                'config' => [
                    'placeholder' => 'Surgical Interviews',
                ],
            ],
        ],
    ];

    $highlightBoxesShowItem = '
        --palette--;;general,
        --palette--;;headers,
        --div--;LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tabs.highlights,
        tx_aistealpproductslider_highlights,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;hidden,
        --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
        rowDescription
    ';

    $GLOBALS['TCA']['tt_content']['types'][$highlightBoxesContentType] = [
        'showitem' => $highlightBoxesShowItem,
        'previewRenderer' => \Aistea\LpBuilder\Backend\ContentElementPreviewRenderer::class,
        'columnsOverrides' => [
            'header' => [
                'config' => [
                    'placeholder' => 'Highlights',
                ],
            ],
            'subheader' => [
                'config' => [
                    'placeholder' => 'Claim',
                ],
            ],
        ],
    ];

    $GLOBALS['TCA']['tt_content']['types'][$heroSequenzContentType] = [
        'showitem' => '
            --palette--;;general,
            --palette--;;headers,
            file_collection,
            scroll_driven,
            desktop_fps,
            desktop_loop,
            mobile_breakpoint,
            mobile_mode,
            mobile_static_image,
            preload_strategy,
            alt_text,
            background_color,
            render_full_list_on_mobile,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;;access,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
            --palette--;;language,
            --palette--;;translationSource,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
            categories,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
            rowDescription,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended
        ',
    ];
})();
