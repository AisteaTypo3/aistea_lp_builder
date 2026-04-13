<?php

declare(strict_types=1);

return [
    'frontend' => [
        'aistea/lp-product-slider-endpoint' => [
            'target' => \Aistea\LpBuilder\Middleware\SlideEndpointMiddleware::class,
            'after' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'before' => [
                'typo3/cms-frontend/tsfe',
            ],
        ],
        'aistea/lp-builder-hero-sequence-endpoint' => [
            'target' => \Aistea\LpBuilder\Middleware\FramesEndpointMiddleware::class,
            'before' => [
                'typo3/cms-frontend/site',
                'typo3/cms-frontend/page-resolver',
            ],
        ],
    ],
];
