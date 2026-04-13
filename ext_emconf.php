<?php

declare(strict_types=1);

$EM_CONF['aistea_lp_builder'] = [
    'title' => 'LP Builder',
    'description' => 'Landing page builder extension bundling product slider, hero sequence and other custom LP content elements',
    'category' => 'plugin',
    'author' => 'Aistea',
    'author_email' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.9.99',
            'fluid_styled_content' => '13.4.0-13.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
