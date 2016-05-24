<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Exception\Strategy;

use Sonata\BlockBundle\Exception\Strategy\StrategyManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test the Exception Strategy Manager.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class StrategyManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StrategyManager
     */
    protected $manager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $filters = array();

    /**
     * @var array
     */
    protected $renderers = array();

    /**
     * @var array
     */
    protected $blockFilters = array();

    /**
     * @var array
     */
    protected $blockRenderers = array();

    /**
     * @var \Sonata\BlockBundle\Exception\Renderer\RendererInterface
     */
    protected $renderer1;

    /**
     * @var \Sonata\BlockBundle\Exception\Renderer\RendererInterface
     */
    protected $renderer2;

    /**
     * @var \Sonata\BlockBundle\Exception\Filter\FilterInterface
     */
    protected $filter1;

    /**
     * @var \Sonata\BlockBundle\Exception\Filter\FilterInterface
     */
    protected $filter2;

    /**
     * setup a basic scenario to avoid long test setup.
     */
    public function setUp()
    {
        $this->renderer1 = $this->getMock('\Sonata\BlockBundle\Exception\Renderer\RendererInterface');
        $this->renderer2 = $this->getMock('\Sonata\BlockBundle\Exception\Renderer\RendererInterface');
        $this->filter1   = $this->getMock('\Sonata\BlockBundle\Exception\Filter\FilterInterface');
        $this->filter2   = $this->getMock('\Sonata\BlockBundle\Exception\Filter\FilterInterface');

        // setup a mock container which contains our mock renderers and filters
        $this->container = $this->getMockContainer(array(
            'service.renderer1' => $this->renderer1,
            'service.renderer2' => $this->renderer2,
            'service.filter1'   => $this->filter1,
            'service.filter2'   => $this->filter2,
        ));

        // setup 2 mock renderers
        $this->renderers = array();
        $this->renderers['renderer1'] = 'service.renderer1';
        $this->renderers['renderer2'] = 'service.renderer2';

        // setup 2 mock filters
        $this->filters = array();
        $this->filters['filter1'] = 'service.filter1';
        $this->filters['filter2'] = 'service.filter2';

        // setup a specific filter and renderer for "type1" blocks
        $this->blockFilters = array('block.type1' => 'filter2');
        $this->blockRenderers = array('block.type1' => 'renderer2');

        // create test object
        $this->manager = new StrategyManager($this->container, $this->filters, $this->renderers, $this->blockFilters, $this->blockRenderers);

        // setup default filters and renderers in manager
        $this->manager->setDefaultFilter('filter1');
        $this->manager->setDefaultRenderer('renderer1');
    }

    /**
     * test getBlockRenderer() with existing block renderer.
     */
    public function testGetBlockRendererWithExisting()
    {
        // GIVEN
        $block = $this->getMockBlock('block.type1');

        // WHEN
        $renderer = $this->manager->getBlockRenderer($block);

        // THEN
        $this->assertNotNull($renderer);
        $this->assertEquals($this->renderer2, $renderer, 'Should return the block type1 renderer');
    }

    /**
     * test getBlockRenderer() with non existing block renderer.
     */
    public function testGetBlockRendererWithNonExisting()
    {
        // GIVEN
        $block = $this->getMockBlock('block.other_type');

        // WHEN
        $renderer = $this->manager->getBlockRenderer($block);

        // THEN
        $this->assertNotNull($renderer);
        $this->assertEquals($this->renderer1, $renderer, 'Should return the default renderer');
    }

    /**
     * test getBlockFilter() with an existing block filter.
     */
    public function testGetBlockFilterWithExisting()
    {
        // GIVEN
        $block = $this->getMockBlock('block.type1');

        // WHEN
        $filter = $this->manager->getBlockFilter($block);

        // THEN
        $this->assertNotNull($filter);
        $this->assertEquals($this->filter2, $filter, 'Should return the block type1 filter');
    }

    /**
     * test getting the default block renderer.
     */
    public function testGetBlockFilterWithNonExisting()
    {
        // GIVEN
        $block = $this->getMockBlock('block.other_type');

        // WHEN
        $filter = $this->manager->getBlockFilter($block);

        // THEN
        $this->assertNotNull($filter);
        $this->assertEquals($this->filter1, $filter, 'Should return the default filter');
    }

    /**
     * test handleException() with a keep none filter.
     */
    public function testHandleExceptionWithKeepNoneFilter()
    {
        // GIVEN
        $this->filter1->expects($this->once())->method('handle')->will($this->returnValue(false));
        //$this->renderer1->expects($this->once())->method('render')->will($this->returnValue('renderer response'));

        $exception = new \Exception();
        $block = $this->getMockBlock('block.other_type');

        // WHEN
        $response = $this->manager->handleException($exception, $block);

        // THEN
        $this->assertNotNull($response, 'should return something');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response, 'should return a response object');
    }

    /**
     * test handleException() with a keep all filter.
     */
    public function testHandleExceptionWithKeepAllFilter()
    {
        // GIVEN
        $this->filter1->expects($this->once())->method('handle')->will($this->returnValue(true));
        $this->renderer1->expects($this->once())->method('render')->will($this->returnValue('renderer response'));

        $exception = new \Exception();
        $block = $this->getMockBlock('block.other_type');

        // WHEN
        $response = $this->manager->handleException($exception, $block);

        // THEN
        $this->assertNotNull($response, 'should return something');
        $this->assertEquals('renderer response', $response, 'should return the renderer response');
    }

    /**
     * Returns a mock block model with given type.
     *
     * @param string $type
     *
     * @return \Sonata\BlockBundle\Model\BlockInterface
     */
    protected function getMockBlock($type)
    {
        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
        $block->expects($this->any())->method('getType')->will($this->returnValue($type));

        return $block;
    }

    /**
     * Returns a mock container with defined services.
     *
     * @param array $services
     *
     * @return ContainerInterface
     */
    protected function getMockContainer(array $services = array())
    {
        $map = array();
        foreach ($services as $name => $service) {
            $map[] = array($name, ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $service);
        }

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())->method('get')->will($this->returnValueMap($map));

        return $container;
    }
}
