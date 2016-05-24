<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Block;

use Sonata\BlockBundle\Block\BlockContext;
use Sonata\BlockBundle\Block\BlockRenderer;

/**
 * Unit test of BlockRenderer class.
 */
class BlockRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Sonata\BlockBundle\Block\BlockServiceManagerInterface
     */
    protected $blockServiceManager;

    /**
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Sonata\BlockBundle\Exception\Strategy\StrategyManager
     */
    protected $exceptionStrategyManager;

    /**
     * @var BlockRenderer
     */
    protected $renderer;

    /**
     * Setup test object.
     */
    public function setUp()
    {
        $this->blockServiceManager = $this->getMock('Sonata\BlockBundle\Block\BlockServiceManagerInterface');
        $this->exceptionStrategyManager = $this->getMock('Sonata\BlockBundle\Exception\Strategy\StrategyManagerInterface');
        $this->logger = $this->getMock('Psr\Log\LoggerInterface');

        $this->renderer = new BlockRenderer($this->blockServiceManager, $this->exceptionStrategyManager, $this->logger);
    }

    /**
     * Test rendering a block without errors.
     */
    public function testRenderWithoutErrors()
    {
        // GIVEN

        // mock a block service that returns a response
        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');
        $service = $this->getMock('Sonata\BlockBundle\Block\BlockServiceInterface');
        $service->expects($this->once())->method('load');
        $service->expects($this->once())->method('execute')->will($this->returnValue($response));
        $this->blockServiceManager->expects($this->once())->method('get')->will($this->returnValue($service));

        // mock a block object
        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
        $blockContext = new BlockContext($block);

        // WHEN
        $result = $this->renderer->render($blockContext);

        // THEN
        $this->assertEquals($response, $result, 'Should return the response from the block service');
    }

    /**
     * Test rendering a block that returns a wrong response.
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage A block service must return a Response object
     */
    public function testRenderWithWrongResponse()
    {
        // GIVEN

        // mock a block service that returns a string response
        $service = $this->getMock('Sonata\BlockBundle\Block\BlockServiceInterface');
        $service->expects($this->once())->method('load');
        $service->expects($this->once())->method('execute')->will($this->returnValue('wrong response'));

        $this->blockServiceManager->expects($this->once())->method('get')->will($this->returnValue($service));

        // mock the exception strategy manager to rethrow the exception
        $this->exceptionStrategyManager->expects($this->once())
            ->method('handleException')
            ->will($this->returnCallback(function ($e) {
                throw $e;
            }));

        // mock the logger to ensure a crit message is logged
        $this->logger->expects($this->once())->method('critical');

        // mock a block object
        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
        $blockContext = new BlockContext($block);

        // WHEN
        $this->renderer->render($blockContext);

        // THEN
        // exception thrown
    }

    /**
     * Test rendering a block that throws an exception.
     */
    public function testRenderBlockWithException()
    {
        // GIVEN

        // mock a block service that throws an user exception
        $service = $this->getMock('Sonata\BlockBundle\Block\BlockServiceInterface');
        $service->expects($this->once())->method('load');

        $exception = $this->getMock('\Exception');
        $service->expects($this->once())
            ->method('execute')
            ->will($this->returnCallback(function () use ($exception) {
                throw $exception;
            }));

        $this->blockServiceManager->expects($this->once())->method('get')->will($this->returnValue($service));

        // mock the exception strategy manager to return a response when given the correct exception
        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');
        $this->exceptionStrategyManager->expects($this->once())
            ->method('handleException')
            ->with($this->equalTo($exception))
            ->will($this->returnValue($response));

        // mock the logger to ensure a crit message is logged
        $this->logger->expects($this->once())->method('critical');

        // mock a block object
        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
        $blockContext = new BlockContext($block);

        // WHEN
        $result = $this->renderer->render($blockContext);

        // THEN
        $this->assertEquals($response, $result, 'Should return the response provider by the exception manager');
    }
}
