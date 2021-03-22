<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\DisallowLongArraySyntaxSniff;
use PhpCsFixer\Fixer\ArrayNotation\TrailingCommaInMultilineArrayFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Operator\IncrementStyleFixer;
use SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

// Run php vendor/bin/ecs check src
// See documentation: https://github.com/symplify/easy-coding-standard

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $parameters = $containerConfigurator->parameters();
    $parameters->set(
        Option::SETS,
        [
            SetList::COMMON,
            SetList::CLEAN_CODE,
            SetList::SYMFONY,
            SetList::PSR_12,
            SetList::PHP_CS_FIXER,
            SetList::DOCTRINE_ANNOTATIONS,
            //SetList::SYMFONY_RISKY,
        ]
    );

    $services->set(DisallowLongArraySyntaxSniff::class);
    $services->set(TrailingCommaInMultilineArrayFixer::class);
    $services->set(\PhpCsFixer\Fixer\Phpdoc\PhpdocNoPackageFixer::class);
    $services->set(\SlevomatCodingStandard\Sniffs\Commenting\UselessFunctionDocCommentSniff::class);
    $services->set(PropertyTypeHintSniff::class);
    $services->set(\SlevomatCodingStandard\Sniffs\Namespaces\FullyQualifiedClassNameAfterKeywordSniff::class);
    $services->set(\PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer::class);
    $services->set(\PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer::class);
    $services->set(\PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer::class);
    $services->set(\PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer::class);
    $services->set(\PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer::class);
    $services->set(\PhpCsFixer\Fixer\ControlStructure\NoUselessElseFixer::class);
    $services->set(\PhpCsFixer\Fixer\ReturnNotation\NoUselessReturnFixer::class);
    $services->set(\PhpCsFixer\Fixer\CastNotation\ModernizeTypesCastingFixer::class);
    $services->set(\PhpCsFixer\Fixer\Casing\ConstantCaseFixer::class);
    $services->set(\PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer::class);
    $services->set(\PhpCsFixer\Fixer\Operator\ConcatSpaceFixer::class)
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
            //__DIR__.'/public/main/admin',
        ]
    );

    $parameters->set(
        Option::SKIP,
        [
            __DIR__.'/public/main/admin/db.php',
            __DIR__.'/src/CoreBundle/Hook/*',
            __DIR__.'/src/CoreBundle/Component/HTMLPurifier/Filter/AllowIframes.php',
            __DIR__.'/src/CoreBundle/Traits/*',
            __DIR__.'/src/CoreBundle/Menu/*',
            __DIR__.'/src/CourseBundle/Component/*',
            __DIR__.'/src/DataFixtures/*',
            //__DIR__.'/src/LtiBundle/*',
            IncrementStyleFixer::class => 'post',
            PropertyTypeHintSniff::class.'.'.PropertyTypeHintSniff::CODE_MISSING_TRAVERSABLE_TYPE_HINT_SPECIFICATION,
            \PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationArrayAssignmentFixer::class,
            \PhpCsFixer\Fixer\Comment\SingleLineCommentStyleFixer::class,
            \PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer::class,
            //\PhpCsFixer\Fixer\Phpdoc\PhpdocOrderFixer::class,
            PhpCsFixer\Fixer\Phpdoc\PhpdocTypesOrderFixer::class,
            PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationSpacesFixer::class,
            PhpCsFixer\Fixer\StringNotation\ExplicitStringVariableFixer::class,
            \Rector\CodingStyle\Rector\Encapsed\WrapEncapsedVariableInCurlyBracesRector::class,
            //UnusedVariableSniff::class . '.ignoreUnusedValuesWhenOnlyKeysAreUsedInForeach' => true,
            //UnusedVariableSniff::class => 'ignoreUnusedValuesWhenOnlyKeysAreUsedInForeach',
        ]
    );
};
