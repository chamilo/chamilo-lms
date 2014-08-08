<?php

namespace ChamiloLMS\InstallerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use ChamiloLMS\InstallerBundle\DependencyInjection\Compiler\InstallerPass;

/**
 * Class ChamiloLMSInstallerBundle
 * @package ChamiloLMS\InstallerBundle
 */
class ChamiloLMSInstallerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new InstallerPass());
    }
}
