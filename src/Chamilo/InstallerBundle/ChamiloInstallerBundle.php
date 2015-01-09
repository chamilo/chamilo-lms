<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\InstallerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Chamilo\InstallerBundle\DependencyInjection\Compiler\InstallerPass;

/**
 * Class ChamiloInstallerBundle
 * @package Chamilo\InstallerBundle
 */
class ChamiloInstallerBundle extends Bundle
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
