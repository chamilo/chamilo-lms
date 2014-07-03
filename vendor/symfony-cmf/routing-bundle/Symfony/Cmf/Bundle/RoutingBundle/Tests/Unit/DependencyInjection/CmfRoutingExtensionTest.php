<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Cmf\Bundle\RoutingBundle\DependencyInjection\CmfRoutingExtension;
use Symfony\Component\DependencyInjection\Reference;

class CmfRoutingExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return array(
            new CmfRoutingExtension(),
        );
    }

    public function testLoadDefault()
    {
        $this->load(array(
            'dynamic' => array(
                'enabled' => true,
                'persistence' => array(
                    'phpcr' => array(
                        'enabled' => true,
                        'use_sonata_admin' => false,
                    ),
                ),
            ),
        ));

        $this->assertContainerBuilderHasAlias('cmf_routing.route_provider', 'cmf_routing.phpcr_route_provider');
        $this->assertContainerBuilderHasAlias('cmf_routing.content_repository', 'cmf_routing.phpcr_content_repository');

        $this->assertContainerBuilderHasParameter('cmf_routing.replace_symfony_router', true);

        $this->assertContainerBuilderHasService('cmf_routing.router', 'Symfony\Cmf\Component\Routing\ChainRouter');
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('cmf_routing.router', 'add', array(
            new Reference('router.default'),
            100,
        ));
    }

    public function testLoadConfigured()
    {
        $this->load(array(
            'dynamic' => array(
                'enabled' => true,
                'route_provider_service_id' => 'test_route_provider_service',
                'content_repository_service_id' => 'test_content_repository_service',
                'persistence' => array(
                    'phpcr' => array(
                        'use_sonata_admin' => false,
                    ),
                ),
            ),
            'chain' => array(
                'routers_by_id' => array(
                    'router.custom' => 200,
                    'router.default' => 300
                )
            )
        ));

        $this->assertContainerBuilderHasAlias('cmf_routing.route_provider', 'test_route_provider_service');
        $this->assertContainerBuilderHasAlias('cmf_routing.content_repository', 'test_content_repository_service');

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('cmf_routing.router', 'add', array(
            new Reference('router.custom'),
            200,
        ));
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('cmf_routing.router', 'add', array(
            new Reference('router.default'),
            300,
        ));
    }

    public function testWhitespaceInPriorities()
    {
        $this->load(array(
            'dynamic' => array(
                'route_provider_service_id' => 'test_route_provider_service',
                'enabled' => true,
                'controllers_by_type' => array(
                    'Acme\Foo' => '
                        acme_main.controller:indexAction
                    '
                ),
            ),
            'chain' => array(
                'routers_by_id' => array(
                    'acme_test.router' => '
                        100
                    ',
                ),
            ),
        ));

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('cmf_routing.router', 'add', array(
            new Reference('acme_test.router'),
            100
        ));

        $this->assertContainerBuilderHasParameter('cmf_routing.controllers_by_type', array(
            'Acme\Foo' => 'acme_main.controller:indexAction',
        ));
    }

    public function testLoadBasePath()
    {
        $this->container->setParameter(
            'kernel.bundles',
            array(
                'CmfRoutingBundle' => true,
                'SonataDoctrinePHPCRAdminBundle' => true,
            )
        );

        $this->load(array(
            'dynamic' => array(
                'enabled' => true,
                'persistence' => array(
                    'phpcr' => array(
                        'enabled' => true,
                        'route_basepath' => '/cms/routes',
                    ),
                ),
            ),
        ));

        $this->assertContainerBuilderHasParameter('cmf_routing.dynamic.persistence.phpcr.admin_basepath', '/cms/routes');
    }

    public function testLoadBasePaths()
    {
        $this->container->setParameter(
            'kernel.bundles',
            array(
                'CmfRoutingBundle' => true,
                'SonataDoctrinePHPCRAdminBundle' => true,
            )
        );

        $this->load(array(
            'dynamic' => array(
                'enabled' => true,
                'persistence' => array(
                    'phpcr' => array(
                        'enabled' => true,
                        'route_basepaths' => array('/cms/routes', '/cms/test'),
                    ),
                ),
            ),
        ));

        $this->assertContainerBuilderHasParameter('cmf_routing.dynamic.persistence.phpcr.admin_basepath', '/cms/routes');
        $this->assertContainerBuilderHasParameter('cmf_routing.dynamic.persistence.phpcr.route_basepaths', array('/cms/routes', '/cms/test'));
    }
}
