<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\DisallowLongArraySyntaxSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\AssignmentInConditionSniff;
use PhpCsFixer\Fixer\Casing\ConstantCaseFixer;
use PhpCsFixer\Fixer\CastNotation\ModernizeTypesCastingFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\Comment\SingleLineCommentStyleFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUselessElseFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationArrayAssignmentFixer;
use PhpCsFixer\Fixer\FunctionNotation\FopenFlagsFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\IncrementStyleFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoPackageFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestClassRequiresCoversFixer;
use PhpCsFixer\Fixer\ReturnNotation\NoUselessReturnFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use SlevomatCodingStandard\Sniffs\Commenting\UselessFunctionDocCommentSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\StandaloneLineInMultilineArrayFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

// Run php vendor/bin/ecs check src
// See documentation: https://github.com/symplify/easy-coding-standard

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->import(SetList::COMMON);
    $ecsConfig->import(SetList::CLEAN_CODE);
    $ecsConfig->dynamicSets(['@Symfony']);
    $ecsConfig->import(SetList::PSR_12);
    $ecsConfig->dynamicSets(['@PhpCsFixer']);
    $ecsConfig->import(SetList::DOCTRINE_ANNOTATIONS);
    $ecsConfig->dynamicSets(['@Symfony:risky']);

    $ecsConfig->rule(DisallowLongArraySyntaxSniff::class);
    $ecsConfig->rule(TrailingCommaInMultilineFixer::class);
    $ecsConfig->rule(PhpdocNoPackageFixer::class);
    $ecsConfig->rule(UselessFunctionDocCommentSniff::class);
    $ecsConfig->rule(PropertyTypeHintSniff::class);
    $ecsConfig->ruleWithConfiguration(
        YodaStyleFixer::class,
        ['equal' => true, 'identical' => true, 'less_and_greater' => false]
    );
    $ecsConfig->rule(NoSuperfluousPhpdocTagsFixer::class);
    $ecsConfig->rule(VoidReturnFixer::class);
    $ecsConfig->rule(DeclareStrictTypesFixer::class);
    $ecsConfig->rule(NoEmptyPhpdocFixer::class);
    $ecsConfig->rule(NoUselessElseFixer::class);
    $ecsConfig->rule(NoUselessReturnFixer::class);
    $ecsConfig->rule(ModernizeTypesCastingFixer::class);
    $ecsConfig->rule(ConstantCaseFixer::class);
    $ecsConfig->rule(OrderedClassElementsFixer::class);
    $ecsConfig->ruleWithConfiguration(
        ConcatSpaceFixer::class,
        [
            'spacing' => 'none',
        ]
    );
    $ecsConfig->ruleWithConfiguration(
        GlobalNamespaceImportFixer::class,
        [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => false,
        ]
    );

    $ecsConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests/CoreBundle',
        __DIR__.'/tests/CourseBundle',
        //__DIR__.'/public/main/admin',
    ]);

    $ecsConfig->skip([
        __DIR__.'/src/CoreBundle/Component/HTMLPurifier/Filter/AllowIframes.php',
        __DIR__.'/src/CoreBundle/Traits/Repository/*',
        __DIR__.'/src/CourseBundle/Component/*',
        __DIR__.'/src/DataFixtures/*',
        __DIR__.'/src/CoreBundle/Entity/ResourceInterface.php',
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
        ClassAttributesSeparationFixer::class,
        FopenFlagsFixer::class,
        ArrayOpenerAndCloserNewlineFixer::class,
        ArrayListItemNewlineFixer::class,
        StandaloneLineInMultilineArrayFixer::class,
        //UnusedVariableSniff::class . '.ignoreUnusedValuesWhenOnlyKeysAreUsedInForeach' => true,
        //UnusedVariableSniff::class => 'ignoreUnusedValuesWhenOnlyKeysAreUsedInForeach',
        AssignmentInConditionSniff::class.'.FoundInWhileCondition',
        AssignmentInConditionSniff::class.'.Found',
        PhpUnitTestClassRequiresCoversFixer::class,
    ]);
};
