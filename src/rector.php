<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;
use SavinMikhail\AddNamedArgumentsRector\AddNamedArgumentsRector;
use SavinMikhail\AddNamedArgumentsRector\Config\DefaultStrategy;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/bootstrap/cache',
    ])
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withCache(__DIR__ . '/storage/framework/cache/rector')
    ->withConfiguredRule(AddNamedArgumentsRector::class, [
        AddNamedArgumentsRector::STRATEGY => DefaultStrategy::class,
        AddNamedArgumentsRector::ALLOW_NAMED_VARIADIC_ARGUMENTS => false,
        AddNamedArgumentsRector::SKIP_CALLS => [
            'Illuminate\Database\Schema\Blueprint::*',
            'Illuminate\Testing\TestResponse::assertJsonStructure',
            'Illuminate\Database\Eloquent\Factories\Factory::count',
            'Database\Factories\*::count',
        ],
    ]);
