<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_interview',
        'label' => 'title',
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
        'searchFields' => 'title,bodytext',
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
                subline,
                bodytext,
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
                'foreign_table' => 'tx_aistealpproductslider_interview',
                'foreign_table_where' => 'AND {#tx_aistealpproductslider_interview}.{#pid}=###CURRENT_PID### AND {#tx_aistealpproductslider_interview}.{#sys_language_uid} IN (-1,0) AND {#tx_aistealpproductslider_interview}.{#l10n_parent}=0',
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
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_interview.title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim,required',
            ],
        ],
        'bodytext' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_interview.bodytext',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'rows' => 5,
            ],
        ],
        'subline' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_interview.subline',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
                'placeholder' => 'Shoulder specialist, UK',
            ],
        ],
        'media_video' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_interview.media_video',
            'config' => [
                'type' => 'file',
                'allowed' => 'vimeo,mp4,webm',
                'maxitems' => 1,
                'minitems' => 1,
            ],
        ],
    ],
];
