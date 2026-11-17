<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use RectorLaravel\Rector\Class_\DescriptionPropertyToDescriptionAttributeRector;
use RectorLaravel\Rector\Class_\FillablePropertyToFillableAttributeRector;
use RectorLaravel\Rector\Class_\HiddenPropertyToHiddenAttributeRector;
use RectorLaravel\Rector\Class_\ReplaceQueueTraitsWithQueueableRector;
use RectorLaravel\Rector\Class_\SignaturePropertyToSignatureAttributeRector;
use RectorLaravel\Rector\Class_\TablePropertyToTableAttributeRector;
use RectorLaravel\Rector\Class_\WithoutIncrementingPropertyToWithoutIncrementingAttributeRector;

// NPIS-only refactoring scope. Legacy application files are intentionally
// excluded so automated refactors cannot alter unrelated production behavior.
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app/Console/Commands/NpisPrune.php',
        __DIR__.'/app/Contracts/NpisCloudStorage.php',
        __DIR__.'/app/Enum/Npis',
        __DIR__.'/app/Exceptions/NpisApiException.php',
        __DIR__.'/app/Http/Controllers/Api/V1',
        __DIR__.'/app/Http/Middleware/Npis',
        __DIR__.'/app/Http/Requests/Npis',
        __DIR__.'/app/Http/Resources/Npis',
        __DIR__.'/app/Jobs/Npis',
        __DIR__.'/app/Mail/NpisNotificationMail.php',
        __DIR__.'/app/Models/Npis',
        __DIR__.'/app/Policies/Npis',
        __DIR__.'/app/Services/Npis',
        __DIR__.'/app/Support/Npis',
        __DIR__.'/database/seeders/Npis',
        __DIR__.'/tests/Feature/Npis',
        __DIR__.'/tests/Support/Npis',
        __DIR__.'/tests/Unit/Npis',
    ])
    ->withComposerBased(laravel: true)
    ->withPhpSets(php84: true)
    ->withSkip([
        FillablePropertyToFillableAttributeRector::class,
        TablePropertyToTableAttributeRector::class,
        WithoutIncrementingPropertyToWithoutIncrementingAttributeRector::class,
        HiddenPropertyToHiddenAttributeRector::class,
        DescriptionPropertyToDescriptionAttributeRector::class,
        SignaturePropertyToSignatureAttributeRector::class,
        ReplaceQueueTraitsWithQueueableRector::class,
        SafeDeclareStrictTypesRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
        instanceOf: true,
        privatization: false,
    )
    ->withImportNames(removeUnusedImports: true);
