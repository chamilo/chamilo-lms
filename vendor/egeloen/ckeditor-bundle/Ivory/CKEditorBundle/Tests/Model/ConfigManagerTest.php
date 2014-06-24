<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Tests\Model;

use Ivory\CKEditorBundle\Model\ConfigManager;

/**
 * Config manager test.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class ConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Ivory\CKEditorBundle\Model\ConfigManager */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configManager = new ConfigManager();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->configManager);
    }

    public function testDefaultState()
    {
        $this->assertNull($this->configManager->getDefaultConfig());
        $this->assertFalse($this->configManager->hasConfigs());
        $this->assertSame(array(), $this->configManager->getConfigs());
    }

    public function testInitialState()
    {
        $configs = array(
            'foo' => array('foo'),
            'bar' => array('bar'),
        );

        $this->configManager = new ConfigManager($configs, 'foo');

        $this->assertSame('foo', $this->configManager->getDefaultConfig());
        $this->assertTrue($this->configManager->hasConfigs());
        $this->assertSame($configs, $this->configManager->getConfigs());
    }

    public function testSetConfig()
    {
        $this->configManager->setConfig('foo', array('foo' => 'bar'));
        $this->configManager->setConfig('foo', $config = array('foo' => 'baz'));

        $this->assertSame($config, $this->configManager->getConfig('foo'));
    }

    public function testMergeConfig()
    {
        $this->configManager->setConfig('foo', $config1 = array('foo' => 'bar', 'bar' => 'foo'));
        $this->configManager->mergeConfig('foo', $config2 = array('foo' => 'baz'));

        $this->assertSame(array_merge($config1, $config2), $this->configManager->getConfig('foo'));
    }

    public function testDefaultCOnfig()
    {
        $this->configManager->setConfig('foo', array('foo' => 'bar'));
        $this->configManager->setDefaultConfig('foo');
    }

    /**
     * @expectedException \Ivory\CKEditorBundle\Exception\ConfigManagerException
     * @expectedExceptionMessage The CKEditor config "foo" does not exist.
     */
    public function testDefaultConfigWithInvalidValue()
    {
        $this->configManager->setDefaultConfig('foo');
    }

    /**
     * @expectedException \Ivory\CKEditorBundle\Exception\ConfigManagerException
     * @expectedExceptionMessage The CKEditor config "foo" does not exist.
     */
    public function testGetConfigWithInvalidName()
    {
        $this->configManager->getConfig('foo');
    }

    /**
     * @expectedException \Ivory\CKEditorBundle\Exception\ConfigManagerException
     * @expectedExceptionMessage The CKEditor config "foo" does not exist.
     */
    public function testMergeConfigWithInvalidName()
    {
        $this->configManager->mergeConfig('foo', array('foo' => 'bar'));
    }
}
