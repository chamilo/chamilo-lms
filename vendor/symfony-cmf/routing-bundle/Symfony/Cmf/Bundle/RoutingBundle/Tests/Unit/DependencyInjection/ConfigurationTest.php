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

use Symfony\Cmf\Bundle\RoutingBundle\DependencyInjection\CmfRoutingExtension;
use Symfony\Cmf\Bundle\RoutingBundle\DependencyInjection\Configuration;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;

class ConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    protected function getContainerExtension()
    {
        return new CmfRoutingExtension();
    }

    protected function getConfiguration()
    {
        return new Configuration();
    }

    public function testSupportsAllConfigFormats()
    {
        $expectedConfiguration = array(
            'chain' => array(
                'routers_by_id' => array(
                    'cmf_routing.router' => 300,
                    'router.default' => 100,
                ),
                'replace_symfony_router' => true,
            ),
            'dynamic' => array(
                'route_collection_limit' => 0,
                'generic_controller' => 'acme_main.controller:mainAction',
                'controllers_by_type' => array(
                    'editable' => 'acme_main.some_controller:editableAction',
                ),
                'controllers_by_class' => array(
                    'Symfony\Cmf\Bundle\ContentBundle\Document\StaticContent' => 'cmf_content.controller:indexAction',
                ),
                'templates_by_class' => array(
                    'Symfony\Cmf\Bundle\ContentBundle\Document\StaticContent' => 'CmfContentBundle:StaticContent:index.html.twig',
                ),
                'persistence' => array(
                    'phpcr' => array(
                        'enabled' => true,
                        'route_basepaths' => array(
                            '/cms/routes',
                            '/simple',
                        ),
                        'content_basepath' => '/cms/content',
                        'manager_name' => null,
                        'use_sonata_admin' => false,
                    ),
                    'orm' => array(
                        'enabled' => false,
                        'manager_name' => null,
                    ),
                ),
                'enabled' => true,
                'default_controller' => null,
                'uri_filter_regexp' => '',
                'route_filters_by_id' => array(),
                'locales' => array('en', 'fr'),
                'limit_candidates' => 20,
                'auto_locale_pattern' => true,
                'match_implicit_locale' => true,
            ),
        );

        $formats = array_map(function ($path) {
            return __DIR__.'/../../Resources/Fixtures/'.$path;
        }, array(
            'config/config.yml',
            'config/config.xml',
            'config/config.php',
        ));

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, array($format));
        }
    }
}
