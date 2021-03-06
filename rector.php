<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();

    // paths to refactor; solid alternative to CLI arguments
    $parameters->set(Option::PATHS, [
        __DIR__.'/src',
    ]);

    // Define what rule sets will be applied
    $parameters->set(Option::SETS, [
        //SetList::DEAD_CODE,
        SetList::CODING_STYLE,
        SetList::CODE_QUALITY,
        SetList::PHP_74,
        SetList::DOCTRINE_CODE_QUALITY,
    ]);

    // register single rule
    $services = $containerConfigurator->services();
    $services->set(TypedPropertyRector::class);

    $services->set(\Rector\TypeDeclaration\Rector\Property\PropertyTypeDeclarationRector::class);
    $services->set(\Rector\TypeDeclaration\Rector\FunctionLike\ParamTypeDeclarationRector::class);
    $services->set(\Rector\TypeDeclaration\Rector\FunctionLike\ReturnTypeDeclarationRector::class);

    //$services->set(\Rector\CakePHP\Rector\FileWithoutNamespace\ImplicitShortClassNameUseStatementRector::class);
    $services->set(\PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer::class);
    $services->set(\PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer::class);
    $services->set(Rector\CodeQuality\Rector\LogicalAnd\LogicalToBooleanRector::class);

    //$services->set(Rector\DoctrineCodeQuality\Rector\ClassMethod\MakeEntitySetterNullabilityInSyncWithPropertyRector::class);
    //$services->set(\Rector\DoctrineCodeQuality\Rector\Property\CorrectDefaultTypesOnEntityPropertyRector::class);
    //$services->set(\Rector\DoctrineCodeQuality\Rector\Property\ImproveDoctrineCollectionDocTypeInEntityRector::class);

    //$services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveUnusedParameterRector::class, false);
    $services->set(\PhpCsFixer\Fixer\Import\OrderedImportsFixer::class);

    /*$parameters->set(
        Option::SYMFONY_CONTAINER_XML_PATH_PARAMETER,
        __DIR__ . '/var/cache/dev/Chamilo_KernelDevDebugContainer.xml'
    );*/

    $parameters->set(
        Option::SKIP,
        [
            __DIR__.'/public/*',
            __DIR__.'/src/CoreBundle/Menu/*',
            __DIR__.'/src/CoreBundle/Component/Editor/*',
            __DIR__.'/src/CourseBundle/Component/CourseCopy/*',
            __DIR__.'/src/CoreBundle/Component/HTMLPurifier/*',
            __DIR__.'/src/LtiBundle/*',
            __DIR__.'/src/CoreBundle/Hook/*',
            __DIR__.'/src/CoreBundle/Migrations/*',
            __DIR__.'/src/CoreBundle/Twig/SettingsHelper.php',
            __DIR__.'/src/CoreBundle/Settings/*',
            //__DIR__.'/src/CoreBundle/Controller/ResourceApiController.php',
            //__DIR__.'/src/CoreBundle/Controller/EditorController.php',
            __DIR__.'/src/CoreBundle/Component/Editor/*',
            \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedParameterRector::class,
            \PhpCsFixer\Fixer\FunctionNotation\UseArrowFunctionsFixer::class,
            \Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class,
            \Rector\CodeQuality\Rector\ClassMethod\DateTimeToDateTimeInterfaceRector::class,
            \Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector::class,
            \Rector\DoctrineCodeQuality\Rector\Property\CorrectDefaultTypesOnEntityPropertyRector::class,
            Rector\TypeDeclaration\Rector\FunctionLike\ReturnTypeDeclarationRector::class,
            //\Rector\DoctrineCodeQuality\Rector\Class_\MoveCurrentDateTimeDefaultInEntityToConstructorRector::class,
            Rector\DoctrineCodeQuality\Rector\Property\RemoveRedundantDefaultPropertyAnnotationValuesRector::class,
            Rector\DoctrineCodeQuality\Rector\Property\ImproveDoctrineCollectionDocTypeInEntityRector::class,
            Rector\CodingStyle\Rector\Switch_\BinarySwitchToIfElseRector::class,
            Rector\CodingStyle\Rector\ClassConst\VarConstantCommentRector::class,
            Rector\DoctrineCodeQuality\Rector\Class_\MoveCurrentDateTimeDefaultInEntityToConstructorRector::class,
            Rector\CodingStyle\Rector\String_\SplitStringClassConstantToClassConstFetchRector::class,
            Rector\Php55\Rector\String_\StringClassNameToClassConstantRector::class,
            Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector::class,



        ]
    );

    //$parameters->set(Option::AUTO_IMPORT_NAMES, true);

    // get services (needed for register a single rule)
    // $services = $containerConfigurator->services();

    // register a single rule
    // $services->set(TypedPropertyRector::class);
};
