<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CourseBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use ChamiloLMS\CourseBundle\DependencyInjection\Compiler\ToolCompilerClass;
class ChamiloLMSCourseBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ToolCompilerClass());
    }
}
