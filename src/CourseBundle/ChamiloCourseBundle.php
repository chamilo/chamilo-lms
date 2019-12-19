<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle;

use Chamilo\CourseBundle\DependencyInjection\Compiler\RegisterSchemasPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ChamiloCourseBundle.
 */
class ChamiloCourseBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterSchemasPass());
    }
}
