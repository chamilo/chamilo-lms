<?php

namespace Mopa\Bundle\BootstrapBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Mopa\Bundle\BootstrapBundle\DependencyInjection\Compiler\FormPass;

/**
 * Bootstrap Extension
 */
class MopaBootstrapBundle extends Bundle
{
    /**
     * Build this
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new FormPass());
    }
}
