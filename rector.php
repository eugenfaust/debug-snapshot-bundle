<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Exception\Configuration\InvalidConfigurationException;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

try {
    return RectorConfig::configure()
        ->withPaths([
            __DIR__.'/src',
        ])
        // uncomment to reach your current PHP version
        ->withPhpSets(php83: true)
        ->withCache(
            cacheDirectory: __DIR__.'/.build/rector',
            cacheClass: FileCacheStorage::class,
        )
        ->withoutParallel()
        ->withSets(
            [
                SymfonySetList::SYMFONY_71,
                SymfonySetList::SYMFONY_CODE_QUALITY,
                SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
                DoctrineSetList::DOCTRINE_CODE_QUALITY,
                DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
                SetList::DEAD_CODE,
                SetList::CODE_QUALITY,
                SetList::TYPE_DECLARATION,
                SetList::PRIVATIZATION,
                //                SetList::NAMING,
                SetList::INSTANCEOF,
                SetList::EARLY_RETURN,
                //                SetList::STRICT_BOOLEANS,
                SetList::RECTOR_PRESET,
            ],
        )
        ->withRules(
            [
                RenameParamToMatchTypeRector::class,
                RenameVariableToMatchNewTypeRector::class,
                RenameVariableToMatchMethodCallReturnTypeRector::class,
                RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class,
                RenameForeachValueVariableToMatchExprVariableRector::class,
            ]
        )
        ->withAttributesSets(symfony: true, doctrine: true)
        ->withComposerBased(twig: true, doctrine: true)
    ;
} catch (InvalidConfigurationException $e) {
    throw new InvalidConfigurationException('Invalid configuration: '.$e->getMessage());
}
