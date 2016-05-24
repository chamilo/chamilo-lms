<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests;

use Sonata\BlockBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testOptions()
    {
        $defaultTemplates = array(
            'SonataPageBundle:Block:block_container.html.twig'       => 'SonataPageBundle template',
            'SonataSeoBundle:Block:block_social_container.html.twig' => 'SonataSeoBundle (to contain social buttons)',
        );

        $processor = new Processor();

        $config = $processor->processConfiguration(new Configuration($defaultTemplates), array(array(
            'default_contexts' => array('cms'),
            'blocks'           => array('my.block.type' => array()),
        )));

        $expected = array(
            'default_contexts' => array(
                0 => 'cms',
            ),
            'profiler' => array(
                'enabled'         => '%kernel.debug%',
                'template'        => 'SonataBlockBundle:Profiler:block.html.twig',
                'container_types' => array(
                    0 => 'sonata.block.service.container',
                    1 => 'sonata.page.block.container',
                    2 => 'cmf.block.container',
                    3 => 'cmf.block.slideshow',
                ),
            ),
            'context_manager' => 'sonata.block.context_manager.default',
            'http_cache'      => array(
                'handler'  => 'sonata.block.cache.handler.default',
                'listener' => true,
            ),
            'templates' => array(
                'block_base'      => null,
                'block_container' => null,
            ),
            'container' => array(
                'types' => array(
                    0 => 'sonata.block.service.container',
                    1 => 'sonata.page.block.container',
                    2 => 'cmf.block.container',
                    3 => 'cmf.block.slideshow',
                ),
                'templates' => $defaultTemplates,
            ),
            'blocks' => array(
                'my.block.type' => array(
                    'contexts' => array('cms'),
                    'cache'    => 'sonata.cache.noop',
                    'settings' => array(),
                ),
            ),
            'menus'           => array(),
            'blocks_by_class' => array(),
            'exception'       => array(
                'default' => array(
                    'filter'   => 'debug_only',
                    'renderer' => 'throw',
                ),
                'filters' => array(
                    'debug_only'             => 'sonata.block.exception.filter.debug_only',
                    'ignore_block_exception' => 'sonata.block.exception.filter.ignore_block_exception',
                    'keep_all'               => 'sonata.block.exception.filter.keep_all',
                    'keep_none'              => 'sonata.block.exception.filter.keep_none',
                ),
                'renderers' => array(
                    'inline'       => 'sonata.block.exception.renderer.inline',
                    'inline_debug' => 'sonata.block.exception.renderer.inline_debug',
                    'throw'        => 'sonata.block.exception.renderer.throw',
                ),
            ),
        );

        $this->assertEquals($expected, $config);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid configuration for path "sonata_block": You cannot have different config options for sonata_block.profiler.container_types and sonata_block.container.types; the first one is deprecated, in case of doubt use the latter
     */
    public function testOptionsDuplicated()
    {
        $defaultTemplates = array(
            'SonataPageBundle:Block:block_container.html.twig'       => 'SonataPageBundle template',
            'SonataSeoBundle:Block:block_social_container.html.twig' => 'SonataSeoBundle (to contain social buttons)',
        );

        $processor = new Processor();
        $processor->processConfiguration(new Configuration($defaultTemplates), array(array(
            'default_contexts' => array('cms'),
            'profiler'         => array('container_types' => array('test_type')),
            'container'        => array('types'           => array('test_type2'), 'templates' => array()),
        )));
    }
}
