<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sonata\CoreBundle\DependencyInjection\Compiler\AdapterCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Ahmet Akbana <ahmetakbana@gmail.com>
 */
final class AdapterCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AdapterCompilerPass());
    }

    public function testDefinitionsAdded()
    {
        $coreModelAdapterChain = new Definition();
        $this->setDefinition('sonata.core.model.adapter.chain', $coreModelAdapterChain);

        $this->registerService('doctrine', 'foo');
        $this->registerService('doctrine_phpcr', 'foo');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sonata.core.model.adapter.chain',
            'addAdapter',
            array(new Reference('sonata.core.model.adapter.doctrine_orm'))
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sonata.core.model.adapter.chain',
            'addAdapter',
            array(new Reference('sonata.core.model.adapter.doctrine_phpcr'))
        );
    }

    public function testDefinitionsRemoved()
    {
        $coreModelAdapterChain = new Definition();
        $this->setDefinition('sonata.core.model.adapter.chain', $coreModelAdapterChain);

        $this->registerService('sonata.core.model.adapter.doctrine_orm', 'foo');
        $this->registerService('sonata.core.model.adapter.doctrine_phpcr', 'foo');

        $this->compile();

        $this->assertContainerBuilderNotHasService('sonata.core.model.adapter.doctrine_orm');
        $this->assertContainerBuilderNotHasService('sonata.core.model.adapter.doctrine_phpcr');
    }
}
