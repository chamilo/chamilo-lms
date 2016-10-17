<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Block;

use Doctrine\Common\Util\ClassUtils;
use Sonata\BlockBundle\Block\BlockContextManager;

class BlockContextManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetWithValidData()
    {
        $service = $this->getMock('Sonata\BlockBundle\Block\AbstractBlockService');

        $service->expects($this->once())->method('configureSettings');

        $blockLoader = $this->getMock('Sonata\BlockBundle\Block\BlockLoaderInterface');

        $serviceManager = $this->getMock('Sonata\BlockBundle\Block\BlockServiceManagerInterface');
        $serviceManager->expects($this->once())->method('get')->will($this->returnValue($service));

        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
        $block->expects($this->once())->method('getSettings')->will($this->returnValue(array()));

        $manager = new BlockContextManager($blockLoader, $serviceManager);

        $blockContext = $manager->get($block);

        $this->assertInstanceOf('Sonata\BlockBundle\Block\BlockContextInterface', $blockContext);

        $this->assertEquals(array(
            'use_cache' => true,
            'extra_cache_keys' => array(),
            'attr' => array(),
            'template' => false,
            'ttl' => 0,
        ), $blockContext->getSettings());
    }

    public function testGetWithSettings()
    {
        $service = $this->getMock('Sonata\BlockBundle\Block\AbstractBlockService');
        $service->expects($this->once())->method('configureSettings');

        $blockLoader = $this->getMock('Sonata\BlockBundle\Block\BlockLoaderInterface');

        $serviceManager = $this->getMock('Sonata\BlockBundle\Block\BlockServiceManagerInterface');
        $serviceManager->expects($this->once())->method('get')->will($this->returnValue($service));

        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
        $block->expects($this->once())->method('getSettings')->will($this->returnValue(array()));

        $blocksCache = array(
            'by_class' => array(ClassUtils::getClass($block) => 'my_cache.service.id'),
        );

        $manager = new BlockContextManager($blockLoader, $serviceManager, $blocksCache);

        $settings = array('ttl' => 1, 'template' => 'custom.html.twig');

        $blockContext = $manager->get($block, $settings);

        $this->assertInstanceOf('Sonata\BlockBundle\Block\BlockContextInterface', $blockContext);

        $this->assertEquals(array(
            'use_cache' => true,
            'extra_cache_keys' => array(
                BlockContextManager::CACHE_KEY => array(
                    'template' => 'custom.html.twig',
                ),
            ),
            'attr' => array(),
            'template' => 'custom.html.twig',
            'ttl' => 1,
        ), $blockContext->getSettings());
    }

    public function testWithInvalidSettings()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $logger->expects($this->exactly(1))->method('error');

        $service = $this->getMock('Sonata\BlockBundle\Block\AbstractBlockService');
        $service->expects($this->exactly(2))->method('configureSettings');

        $blockLoader = $this->getMock('Sonata\BlockBundle\Block\BlockLoaderInterface');

        $serviceManager = $this->getMock('Sonata\BlockBundle\Block\BlockServiceManagerInterface');
        $serviceManager->expects($this->exactly(2))->method('get')->will($this->returnValue($service));

        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
        $block->expects($this->once())->method('getSettings')->will($this->returnValue(array(
            'template' => array(),
        )));

        $manager = new BlockContextManager($blockLoader, $serviceManager, array(), $logger);

        $blockContext = $manager->get($block);

        $this->assertInstanceOf('Sonata\BlockBundle\Block\BlockContextInterface', $blockContext);
    }

//    @TODO: Think if the BlockContextManager should throw an exception if the resolver throw an exception
//    /**
//     * @expectedException \Sonata\BlockBundle\Exception\BlockOptionsException
//     */
//    public function testGetWithException()
//    {
//        $service = $this->getMock('Sonata\BlockBundle\Block\BlockServiceInterface');
//        $service->expects($this->exactly(2))->method('setDefaultSettings');
//
//        $blockLoader = $this->getMock('Sonata\BlockBundle\Block\BlockLoaderInterface');
//
//        $serviceManager = $this->getMock('Sonata\BlockBundle\Block\BlockServiceManagerInterface');
//        $serviceManager->expects($this->exactly(2))->method('get')->will($this->returnValue($service));
//
//        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
//        $block->expects($this->once())->method('getSettings')->will($this->returnValue(array(
//            'template' => array()
//        )));
//
//        $manager = new BlockContextManager($blockLoader, $serviceManager);
//
//        $blockContext = $manager->get($block);
//
//        $this->assertInstanceOf('Sonata\BlockBundle\Block\BlockContextInterface', $blockContext);
//    }
}
