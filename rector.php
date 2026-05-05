<?php
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Doctrine\Bundle230\Rector\Class_\AddAnnotationToRepositoryRector;
use Rector\Php80\Rector\Class_\StringableForToStringRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;

/** @noinspection PhpUnhandledExceptionInspection */
return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src'])
    ->withSymfonyContainerPhp(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.php')
    ->withPhpSets(php74: true)
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
        codeQuality: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true
    )
    ->withComposerBased(
        symfony: true,
    )
    ->withRules([
        AddParamTypeDeclarationRector::class,
    ])
    ->withSkip([
        AddAnnotationToRepositoryRector::class,
        StringableForToStringRector::class,
    ])
;
