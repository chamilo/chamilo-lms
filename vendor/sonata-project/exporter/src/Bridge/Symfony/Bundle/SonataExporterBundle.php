<?php

declare(strict_types=1);

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
use Sonata\Exporter\Bridge\Symfony\DependencyInjection\SonataExporterExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SonataExporterBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ExporterCompilerPass());
    }

    protected function getContainerExtensionClass(): string
    {
        return SonataExporterExtension::class;
    }
}
