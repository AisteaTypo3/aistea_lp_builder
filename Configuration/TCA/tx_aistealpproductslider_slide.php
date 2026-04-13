<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide',
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
        'searchFields' => 'title,bodytext,aria_label,slide_type',
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
                bodytext,
                slide_type,
                aria_label,
                --palette--;LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tabs.media;media,
                media_image,
                media_video,
                video_poster,
                video_startframe,
                video_endframe,
                model_gltf,
                model_poster,
                model_envmap,
                model_autorotate,
                model_camera_preset,
                model_bg_color,
                color_variants,
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
                'foreign_table' => 'tx_aistealpproductslider_slide',
                'foreign_table_where' => 'AND {#tx_aistealpproductslider_slide}.{#pid}=###CURRENT_PID### AND {#tx_aistealpproductslider_slide}.{#sys_language_uid} IN (-1,0) AND {#tx_aistealpproductslider_slide}.{#l10n_parent}=0',
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
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim,required',
            ],
        ],
        'bodytext' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.bodytext',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'rows' => 5,
            ],
        ],
        'slide_type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.slide_type',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'image',
                'items' => [
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:slide_type.image', 'image'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:slide_type.video', 'video'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:slide_type.model3d', 'model3d'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:slide_type.colorGallery', 'colorGallery'],
                ],
            ],
        ],
        'aria_label' => [
            'exclude' => true,
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.aria_label',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
        'media_image' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:image',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.media_image',
            'config' => [
                'type' => 'file',
                'allowed' => 'jpg,jpeg,png,webp,avif,svg',
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'media_video' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:video',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.media_video',
            'config' => [
                'type' => 'file',
                'allowed' => 'mp4',
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'video_poster' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:video',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.video_poster',
            'config' => [
                'type' => 'file',
                'allowed' => 'jpg,jpeg,png,webp,avif',
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'video_startframe' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:video',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.video_startframe',
            'config' => [
                'type' => 'file',
                'allowed' => 'jpg,jpeg,png,webp,avif',
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'video_endframe' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:video',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.video_endframe',
            'config' => [
                'type' => 'file',
                'allowed' => 'jpg,jpeg,png,webp,avif',
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'model_gltf' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:model3d',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.model_gltf',
            'config' => [
                'type' => 'file',
                'allowed' => 'glb,gltf',
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'model_poster' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:model3d',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.model_poster',
            'config' => [
                'type' => 'file',
                'allowed' => 'jpg,jpeg,png,webp,avif',
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'model_envmap' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:model3d',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.model_envmap',
            'config' => [
                'type' => 'file',
                'allowed' => 'hdr,exr,jpg,jpeg,png,webp',
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'model_autorotate' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:model3d',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.model_autorotate',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'model_camera_preset' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:model3d',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.model_camera_preset',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'front',
                'items' => [
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:camera.front', 'front'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:camera.back', 'back'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:camera.left', 'left'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:camera.right', 'right'],
                    ['LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:camera.custom', 'custom'],
                ],
            ],
        ],
        'model_bg_color' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:model3d',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.model_bg_color',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'placeholder' => '#111111',
            ],
        ],
        'color_variants' => [
            'exclude' => true,
            'displayCond' => 'FIELD:slide_type:=:colorGallery',
            'label' => 'LLL:EXT:aistea_lp_builder/Resources/Private/Language/locallang_db.xlf:tx_aistealpproductslider_slide.color_variants',
            'description' => 'JSON object, e.g. [{"name":"Blue","images":["/fileadmin/a.jpg"]}]',
            'config' => [
                'type' => 'text',
                'rows' => 6,
                'eval' => 'trim',
            ],
        ],
    ],
];
