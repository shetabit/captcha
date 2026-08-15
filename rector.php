<?php

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        // The token of a captcha is passed around the way it came in, where a
        // numeric string standing in for a number is normal.
        SafeDeclareStrictTypesRector::class,

        // Turns `$model !== null` into `$model instanceof Model` where the
        // parameter already declares `Model|null`, which reads worse.
        FlipTypeControlToUseExclusiveTypeRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withPhpSets();
