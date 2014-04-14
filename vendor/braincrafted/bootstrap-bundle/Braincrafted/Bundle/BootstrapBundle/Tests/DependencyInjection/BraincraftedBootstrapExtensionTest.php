<?php

namespace Braincrafted\Bundle\BootstrapBundle\Tests\DependencyInjection;

use \Mockery as m;

use Braincrafted\Bundle\BootstrapBundle\DependencyInjection\BraincraftedBootstrapExtension;

/**
 * BraincraftedBootstrapExtensionTest
 *
 * @group unit
 */
class BraincraftedBootstrapExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->extension = new BraincraftedBootstrapExtension;
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\BraincraftedBootstrapExtension::load()
     */
    public function testLoad()
    {
        $bag = m::mock('Symfony\Component\DependencyInjection\ParameterBag\ParameterBag');
        $bag->shouldReceive('add');

        $container = m::mock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->shouldReceive('hasExtension')->andReturn(false);
        $container->shouldReceive('addResource');
        $container->shouldReceive('getParameterBag')->andReturn($bag);
        $container->shouldReceive('setDefinition');
        $container->shouldReceive('setParameter');

        $this->extension->load(array(), $container);
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\BraincraftedBootstrapExtension::prepend()
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\BraincraftedBootstrapExtension::configureAsseticBundle()
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\BraincraftedBootstrapExtension::configureTwigBundle()
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\BraincraftedBootstrapExtension::configureKnpMenuBundle()
     * @covers Braincrafted\Bundle\BootstrapBundle\DependencyInjection\BraincraftedBootstrapExtension::configureKnpPaginatorBundle()
     */
    public function testPrepend()
    {
        $bundles = array(
            'AsseticBundle' => '',
            'TwigBundle'    => '',
            'KnpMenuBundle'    => '',
            'KnpPaginatorBundle'    => ''
        );

        $extensions = array(
            'assetic' => array(),
            'twig'    => array(),
            'knp_menu' => array(),
            'knp_paginator' => array()
        );
        $container = m::mock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->shouldReceive('getParameter')->with('kernel.bundles')->andReturn($bundles);
        $container->shouldReceive('getExtensions')->andReturn($extensions);
        $container->shouldReceive('getExtensionConfig')->andReturn(array());
        $container->shouldReceive('prependExtensionConfig');

        $this->extension->prepend($container);
    }
}
