<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_hslide',
        'label' => 'title',
        'label_alt' => 'slide_type',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'title,headline,slide_type',
        'iconfile' => 'EXT:aistea_lp_builder/Resources/Public/Icons/ContentProductSlider.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --palette--;;general,
                title,
                headline,
                slide_type,
                media_image,
                media_sequence,
                sequence_fps,
                media_video,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,
                --palette--;;access
            ',
        ],
    ],
    'palettes' => [
        'general' => ['showitem' => 'parenttable,parentid'],
        'language' => ['showitem' => 'sys_language_uid,l10n_parent'],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'lower' => mktime(0, 0, 0, 1, 1, 2000),
                ],
            ],
        ],
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [['', 0]],
                'foreign_table' => 'tx_aistealpproductslider_hslide',
                'foreign_table_where' => 'AND {#tx_aistealpproductslider_hslide}.{#pid}=###CURRENT_PID### AND {#tx_aistealpproductslider_hslide}.{#sys_language_uid} IN (-1,0) AND {#tx_aistealpproductslider_hslide}.{#l10n_parent}=0',
                'default' => 0,
            ],
        ],
        'parentid' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'parenttable' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_hslide.title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim,required',
            ],
        ],
        'headline' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_hslide.headline',
            'config' => [
                'type' => 'input',
                'size' => 60,
                'eval' => 'trim',
            ],
        ],
        'slide_type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_hslide.slide_type',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'image',
                'items' => [
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:hslide_type.image', 'image'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:hslide_type.sequence', 'sequence'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:hslide_type.video', 'video'],
                ],
            ],
        ],
        'media_image' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:image',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_hslide.media_image',
            'config' => [
                'type' => 'file',
                'allowed' => 'jpg,jpeg,png,webp,avif',
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'media_sequence' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:sequence',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_hslide.media_sequence',
            'config' => [
                'type' => 'file',
                'allowed' => 'jpg,jpeg,png,webp,avif',
                'maxitems' => 200,
                'minitems' => 1,
            ],
        ],
        'sequence_fps' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:sequence',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_hslide.sequence_fps',
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
        'media_video' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:video',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_hslide.media_video',
            'config' => [
                'type' => 'file',
                'allowed' => 'mp4,webm',
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
    ],
];
