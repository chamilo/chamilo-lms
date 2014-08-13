<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Chamilo\CourseBundle\DependencyInjection\Compiler\ToolCompilerClass;

class ChamiloCourseBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ToolCompilerClass());
    }
}
