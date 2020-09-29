<?php

/* For licensing terms, see /license.txt */

// Run php vendor/bin/ecs check src
// See documentation: https://github.com/symplify/easy-coding-standard

declare(strict_types=1);

use PhpCsFixer\Fixer\Operator\IncrementStyleFixer;
use SlevomatCodingStandard\Sniffs\Variables\UnusedVariableSniff;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
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

    $parameters->set(
        Option::EXCLUDE_PATHS,
        [
            __DIR__.'/src/CourseBundle/Component/*',
        ]
    );

    // use $a++ instead of ++$a
    $parameters->set(
        Option::SKIP,
        [
            IncrementStyleFixer::class => 'post',
            //UnusedVariableSniff::class . '.ignoreUnusedValuesWhenOnlyKeysAreUsedInForeach' => true,
            UnusedVariableSniff::class => 'ignoreUnusedValuesWhenOnlyKeysAreUsedInForeach',
        ]
    );
};
