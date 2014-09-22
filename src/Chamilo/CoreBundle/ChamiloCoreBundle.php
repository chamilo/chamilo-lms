<?php

namespace Chamilo\CoreBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\EntityListenerPass;
use Chamilo\CoreBundle\DependencyInjection\Compiler\DoctrineEntityListenerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
