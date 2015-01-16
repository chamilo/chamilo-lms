<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle;

use Chamilo\CoreBundle\DependencyInjection\Compiler\DoctrineEntityListenerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ChamiloCoreBundle
 * @package Chamilo\CoreBundle
 */
class ChamiloCoreBundle extends Bundle
{
    public function boot()
    {
        // Add legacy calls.
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        //$container->addCompilerPass(new EntityListenerPass());
        //$container->addCompilerPass(new DoctrineEntityListenerPass());
    }
}
