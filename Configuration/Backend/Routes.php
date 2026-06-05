<?php

declare(strict_types=1);

return [
    'aistea_lp_builder_duplicate_interview' => [
        'path' => '/aistea-lp-builder/interview/duplicate',
        'target' => \Aistea\LpBuilder\Controller\Backend\DuplicateInterviewController::class . '::duplicateAction',
    ],
];
