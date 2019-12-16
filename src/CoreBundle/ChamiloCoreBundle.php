<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle;

use Chamilo\CoreBundle\DependencyInjection\Compiler\ToolCompilerClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ChamiloCoreBundle.
 */
class ChamiloCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new ToolCompilerClass());
    }
}
