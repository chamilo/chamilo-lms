<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Exporter\Bridge\Symfony\Bundle;

use Sonata\Exporter\Bridge\Symfony\DependencyInjection\Compiler\ExporterCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SonataExporterBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ExporterCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerExtensionClass()
    {
        return 'Exporter\Bridge\Symfony\DependencyInjection\SonataExporterExtension';
    }
}

class_exists(\Exporter\Bridge\Symfony\Bundle\SonataExporterBundle::class);
