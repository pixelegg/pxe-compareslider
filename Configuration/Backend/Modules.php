<?php

declare(strict_types=1);

use Pixelegg\PxeCompareslider\Controller\Backend\OverviewController;
use TYPO3\CMS\Core\Information\Typo3Version;

// Top-level module right after the page module group: "web" in TYPO3 13, "content" in 14.
// "web" is only an alias in 14, and the module sorting does not resolve aliases, so the
// position would be ignored there.
$position = ['after' => (new Typo3Version())->getMajorVersion() >= 14 ? 'content' : 'web'];

return [
    'pxe_compareslider' => [
        'position' => $position,
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
