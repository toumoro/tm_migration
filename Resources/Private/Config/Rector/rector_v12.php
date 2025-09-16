<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PostRector\Rector\NameImportingPostRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\CodeQuality\General\ConvertImplicitVariablesToExplicitGlobalsRector;
use Ssch\TYPO3Rector\CodeQuality\General\ExtEmConfRector;
use Ssch\TYPO3Rector\CodeQuality\General\GeneralUtilityMakeInstanceToConstructorPropertyRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/packages/'])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withSets([
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_12,
    ])
    # To have a better analysis from PHPStan, we teach it here some more things
    ->withPHPStanConfigs([
        Typo3Option::PHPSTAN_FOR_RECTOR_PATH
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
        ConvertImplicitVariablesToExplicitGlobalsRector::class,
    ])
    ->withConfiguredRule(ExtEmConfRector::class, [
        ExtEmConfRector::PHP_VERSION_CONSTRAINT => '8.1.0-8.3.99',
        ExtEmConfRector::TYPO3_VERSION_CONSTRAINT => '12.4.0-12.4.99',
        ExtEmConfRector::ADDITIONAL_VALUES_TO_BE_REMOVED => []
    ])
    # If you use withImportNames(), you should consider excluding some TYPO3 files.
    ->withSkip([
        /*custom Mehdi*/
        __DIR__ . '/var/cache',
        __DIR__ . '/vendor',
        __DIR__ . '/mysql',
        __DIR__ . '/packages/tm_quotes/Tests',
        // @see https://github.com/sabbelasichon/typo3-rector/issues/2536
        __DIR__ . '/**/Configuration/ExtensionBuilder/*',
        NameImportingPostRector::class => [
           'ClassAliasMap.php',
        ],
        // Exlclude non autoloaded classes from DI injection
        // @see https://github.com/sabbelasichon/typo3-rector/issues/4604
        GeneralUtilityMakeInstanceToConstructorPropertyRector::class => [
            __DIR__ . '/packages/**/Domain/Model/*',
            __DIR__ . '/packages/**/Hooks/*',
            __DIR__ . '/packages/**/Hook/*',
            __DIR__ . '/packages/**/UserFunc/*',
            __DIR__ . '/packages/**/Userfunc/*',
        ],
    ])
;
