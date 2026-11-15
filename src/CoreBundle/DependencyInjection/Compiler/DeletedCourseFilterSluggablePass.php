<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DependencyInjection\Compiler;

use Chamilo\CoreBundle\Doctrine\Filter\DeletedCourseFilter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The course code is a unique slug: when Gedmo makes it unique, it must also see the codes of
 * deleted courses, which stay reserved (their rows are kept).
 */
final class DeletedCourseFilterSluggablePass implements CompilerPassInterface
{
    private const string SLUGGABLE_LISTENER = 'stof_doctrine_extensions.listener.sluggable';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::SLUGGABLE_LISTENER)) {
            return;
        }

        $container->getDefinition(self::SLUGGABLE_LISTENER)
            ->addMethodCall('addManagedFilter', [DeletedCourseFilter::NAME, true])
        ;
    }
}
