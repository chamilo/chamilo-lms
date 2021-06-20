<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\DisallowLongArraySyntaxSniff;
use PhpCsFixer\Fixer\Casing\ConstantCaseFixer;
use PhpCsFixer\Fixer\CastNotation\ModernizeTypesCastingFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\Comment\SingleLineCommentStyleFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUselessElseFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationArrayAssignmentFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\IncrementStyleFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoPackageFixer;
use PhpCsFixer\Fixer\ReturnNotation\NoUselessReturnFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use Rector\CodingStyle\Rector\Encapsed\WrapEncapsedVariableInCurlyBracesRector;
use SlevomatCodingStandard\Sniffs\Commenting\UselessFunctionDocCommentSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

// Run php vendor/bin/ecs check src
// See documentation: https://github.com/symplify/easy-coding-standard

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $parameters = $containerConfigurator->parameters();

    $containerConfigurator->import(SetList::COMMON);
    $containerConfigurator->import(SetList::CLEAN_CODE);
    $containerConfigurator->import(SetList::SYMFONY);
    $containerConfigurator->import(SetList::PSR_12);
    $containerConfigurator->import(SetList::PHP_CS_FIXER);
    //$containerConfigurator->import(SetList::DOCTRINE_ANNOTATIONS);
    $containerConfigurator->import(SetList::SYMFONY_RISKY);

    $services->set(DisallowLongArraySyntaxSniff::class);
    $services->set(TrailingCommaInMultilineFixer::class);
    $services->set(PhpdocNoPackageFixer::class);
    $services->set(UselessFunctionDocCommentSniff::class);
    $services->set(PropertyTypeHintSniff::class);
    //$services->set(\SlevomatCodingStandard\Sniffs\Namespaces\FullyQualifiedClassNameAfterKeywordSniff::class);
    $services->set(YodaStyleFixer::class);
    $services->set(NoSuperfluousPhpdocTagsFixer::class);
    $services->set(VoidReturnFixer::class);
    $services->set(DeclareStrictTypesFixer::class);
    $services->set(NoEmptyPhpdocFixer::class);
    $services->set(NoUselessElseFixer::class);
    $services->set(NoUselessReturnFixer::class);
    $services->set(ModernizeTypesCastingFixer::class);
    $services->set(ConstantCaseFixer::class);
    $services->set(OrderedClassElementsFixer::class);
    $services->set(ConcatSpaceFixer::class)
        ->call(
            'configure',
            [
                [
                    'spacing' => 'none',
                ],
            ]
        );
    $services->set(GlobalNamespaceImportFixer::class)
        ->call(
            'configure',
            [
                [
                    'import_classes' => true,
                    'import_constants' => true,
                    'import_functions' => false,
                ],
            ]
        );

    $parameters->set(
        Option::PATHS,
        [
            __DIR__.'/src',
            __DIR__.'/tests/CoreBundle',
            __DIR__.'/tests/CourseBundle',
            //__DIR__.'/public/main/admin',
        ]
    );

    $parameters->set(
        Option::SKIP,
        [
            __DIR__.'/public/main/admin/db.php',
            __DIR__.'/src/CoreBundle/Hook/*',
            __DIR__.'/src/CoreBundle/Component/HTMLPurifier/Filter/AllowIframes.php',
            __DIR__.'/src/CoreBundle/Traits/Repository/*',
            __DIR__.'/src/CourseBundle/Component/*',
            __DIR__.'/src/DataFixtures/*',
            IncrementStyleFixer::class => 'post',
            PropertyTypeHintSniff::class.'.'.PropertyTypeHintSniff::CODE_MISSING_TRAVERSABLE_TYPE_HINT_SPECIFICATION,
            PropertyTypeHintSniff::class.'.'.PropertyTypeHintSniff::CODE_MISSING_NATIVE_TYPE_HINT,
            PhpCsFixer\Fixer\PhpUnit\PhpUnitInternalClassFixer::class,
            DoctrineAnnotationArrayAssignmentFixer::class,
            SingleLineCommentStyleFixer::class,
            NotOperatorWithSuccessorSpaceFixer::class,
            //\PhpCsFixer\Fixer\Phpdoc\PhpdocOrderFixer::class,
            PhpCsFixer\Fixer\Phpdoc\PhpdocTypesOrderFixer::class,
            PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationSpacesFixer::class,
            PhpCsFixer\Fixer\StringNotation\ExplicitStringVariableFixer::class,
            WrapEncapsedVariableInCurlyBracesRector::class,
            ClassAttributesSeparationFixer::class,
            \PhpCsFixer\Fixer\FunctionNotation\FopenFlagsFixer::class,
            \Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer::class,
            \Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer::class,
            \Symplify\CodingStandard\Fixer\ArrayNotation\StandaloneLineInMultilineArrayFixer::class,
            //UnusedVariableSniff::class . '.ignoreUnusedValuesWhenOnlyKeysAreUsedInForeach' => true,
            //UnusedVariableSniff::class => 'ignoreUnusedValuesWhenOnlyKeysAreUsedInForeach',
        ]
    );
};
