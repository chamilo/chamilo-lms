<?php

/* For licensing terms, see /license.txt */

// Run php vendor/bin/ecs check src
// See documentation: https://github.com/symplify/easy-coding-standard

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\DisallowLongArraySyntaxSniff;
use PhpCsFixer\Fixer\ArrayNotation\TrailingCommaInMultilineArrayFixer;
use PhpCsFixer\Fixer\Operator\IncrementStyleFixer;
//use SlevomatCodingStandard\Sniffs\Variables\UnusedVariableSniff;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(DisallowLongArraySyntaxSniff::class);
    $services->set(TrailingCommaInMultilineArrayFixer::class);

    $parameters = $containerConfigurator->parameters();
    $parameters->set(
        Option::SETS,
        [
            SetList::CLEAN_CODE,
            SetList::SYMFONY,
            //SetList::SYMFONY_RISKY,
        ]
    );

    $parameters->set(
        Option::PATHS,
        [
            __DIR__.'/src',
        ]
    );

    /*$parameters->set(
        Option::SKIP,
        [
            __DIR__.'/src/CourseBundle/Component/*',
        ]
    );*/

    // use $a++ instead of ++$a
    $parameters->set(
        Option::SKIP,
        [
            IncrementStyleFixer::class => 'post',
            //UnusedVariableSniff::class . '.ignoreUnusedValuesWhenOnlyKeysAreUsedInForeach' => true,
            //UnusedVariableSniff::class => 'ignoreUnusedValuesWhenOnlyKeysAreUsedInForeach',
        ]
    );
};
