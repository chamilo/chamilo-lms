<?php

/* For licensing terms, see /license.txt */

// Run php vendor/bin/ecs check src
// See documentation: https://github.com/symplify/easy-coding-standard

declare(strict_types=1);

use PhpCsFixer\Fixer\Operator\IncrementStyleFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\Configuration\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::SETS, [
        SetList::CLEAN_CODE,
        SetList::SYMFONY,
        //SetList::SYMFONY_RISKY,
    ]);

    // use $a++ instead of ++$a
    $parameters->set(Option::SKIP, [IncrementStyleFixer::class => 'post']);
};
