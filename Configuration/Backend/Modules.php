<?php

declare(strict_types=1);

use Pixelegg\PxeCompareslider\Controller\Backend\OverviewController;

return [
    'pxe_compareslider' => [
        'position' => ['after' => 'web'],
        'standalone' => true,
        'access' => 'user',
        'workspaces' => 'live',
        'iconIdentifier' => 'content-pxe-compareslider',
        'path' => '/module/pxe-compareslider',
        'labels' => 'LLL:EXT:pxe_compareslider/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => OverviewController::class . '::indexAction',
            ],
        ],
    ],
];
