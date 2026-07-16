<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_3dhotspot',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'typeicon_classes' => [
            'default' => 'aistea-lp-3d-hotspot-viewer-ce',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    ['label' => '', 'invertStateDisplay' => true],
                ],
            ],
        ],
        'sys_language_uid' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => ['type' => 'language'],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [['label' => '', 'value' => 0]],
                'foreign_table' => 'tx_aistealpproductslider_3dhotspot',
                'foreign_table_where' => 'AND {#tx_aistealpproductslider_3dhotspot}.{#pid}=###CURRENT_PID### AND {#tx_aistealpproductslider_3dhotspot}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => ['type' => 'passthrough'],
        ],
        'parentid' => [
            'config' => ['type' => 'passthrough'],
        ],
        'parenttable' => [
            'config' => ['type' => 'passthrough'],
        ],
        'title' => [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_3dhotspot.title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_3dhotspot.description',
            'config' => [
                'type' => 'text',
                'rows' => 4,
                'cols' => 60,
                'eval' => 'trim',
            ],
        ],
        'pos_x' => [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_3dhotspot.pos_x',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'default' => '0.000000',
                'eval' => 'trim,required',
                'placeholder' => '0.004740',
            ],
        ],
        'pos_y' => [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_3dhotspot.pos_y',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'default' => '0.000000',
                'eval' => 'trim,required',
                'placeholder' => '0.023972',
            ],
        ],
        'pos_z' => [
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_3dhotspot.pos_z',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'default' => '0.000000',
                'eval' => 'trim,required',
                'placeholder' => '0.010634',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --palette--;;language,
                hidden,
                title,
                description,
                --palette--;;position,
            ',
        ],
    ],
    'palettes' => [
        'language' => ['showitem' => 'sys_language_uid, l10n_parent'],
        'position' => ['showitem' => 'pos_x, pos_y, pos_z'],
    ],
];
