<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/Tests',
    ])
    ->withPhpSets(php82: true)
    ->withSkip([
        // addPlugin() takes 3 arguments on TYPO3 13 and 2 on 14; the file branches on the version.
        RemoveExtraParametersRector::class => [__DIR__ . '/Configuration/TCA/Overrides/tt_content.php'],
    ]);
