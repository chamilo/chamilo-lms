<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Test;

use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\BlockContextManager;
use Sonata\BlockBundle\Block\BlockContextManagerInterface;
use Sonata\BlockBundle\Block\BlockServiceInterface;
use Sonata\BlockBundle\Block\BlockServiceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract test class for block service tests.
 *
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
abstract class AbstractBlockServiceTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|BlockServiceManagerInterface
     */
    protected $blockServiceManager;

    /**
     * @var BlockContextManagerInterface
     */
    protected $blockContextManager;

    /**
     * @var FakeTemplating
     */
    protected $templating;

    protected function setUp()
    {
        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->templating = new FakeTemplating();

        $blockLoader = $this->createMock('Sonata\BlockBundle\Block\BlockLoaderInterface');
        $this->blockServiceManager = $this->createMock('Sonata\BlockBundle\Block\BlockServiceManagerInterface');
        $this->blockContextManager = new BlockContextManager($blockLoader, $this->blockServiceManager);
    }

    /**
     * Create a mocked block service.
     *
     * @param BlockServiceInterface $blockService A block service
     *
     * @return BlockContextInterface
     */
    protected function getBlockContext(BlockServiceInterface $blockService)
    {
        $this->blockServiceManager->expects($this->once())->method('get')->will($this->returnValue($blockService));

        $block = $this->createMock('Sonata\BlockBundle\Model\BlockInterface');
        $block->expects($this->once())->method('getSettings')->will($this->returnValue(array()));

        $blockContext = $this->blockContextManager->get($block);
        $this->assertInstanceOf('Sonata\BlockBundle\Block\BlockContextInterface', $blockContext);

        return $blockContext;
    }

    /**
     * Asserts that the block settings have the expected values.
     *
     * @param array                 $expected     Expected settings
     * @param BlockContextInterface $blockContext BlockContext object
     */
    protected function assertSettings(array $expected, BlockContextInterface $blockContext)
    {
        $completeExpectedOptions = array_merge(array(
            'use_cache' => true,
            'extra_cache_keys' => array(),
            'attr' => array(),
            'template' => false,
            'ttl' => 0,
        ), $expected);

        ksort($completeExpectedOptions);
        $blockSettings = $blockContext->getSettings();
        ksort($blockSettings);

        $this->assertSame($completeExpectedOptions, $blockSettings);
    }

    /**
     * NEXT_MAJOR: Remove this method when dropping support for < PHPUnit 5.4.
     *
     * @param string $class
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMock($class)
    {
        if (is_callable('parent::createMock')) {
            return parent::createMock($class);
        }

        return $this->getMock($class);
    }
}
