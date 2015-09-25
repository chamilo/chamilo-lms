<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return HttpAdapterInterface
     */
    protected function getMockAdapter($expects = null)
    {
        if (null === $expects) {
            $expects = $this->once();
        }

        $mock = $this->getMock('\HttpAdapter\HttpAdapterInterface');
        $mock
            ->expects($expects)
            ->method('getContent')
            ->will($this->returnArgument(0));

        return $mock;
    }

    /**
     * @return HttpAdapterInterface
     */
    protected function getMockAdapterReturns($returnValue)
    {
        $mock = $this->getMock('\HttpAdapter\HttpAdapterInterface');
        $mock
            ->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($returnValue));

        return $mock;
    }

    /**
     * @return Crendentials
     */
    protected function getMockCredentials($expects = null)
    {
        if (null === $expects) {
            $expects = $this->atLeastOnce();
        }

        $mock = $this
            ->getMockBuilder('\DigitalOcean\Credentials')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($expects)
            ->method('getClientId')
            ->will($this->returnValue('foo'));
        $mock
            ->expects($expects)
            ->method('getApiKey')
            ->will($this->returnValue('bar'));

        return $mock;
    }

    /**
     * @return DigitalOcean
     */
    protected function getMockDigitalOcean($method, $returnValue)
    {
        $mock = $this->getMockBuilder('\DigitalOcean\DigitalOcean')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($this->any())
            ->method($method)
            ->will($this->returnValue($returnValue));

        return $mock;
    }


    /**
     * @return Droplets
     */
    protected function getMockDroplets($method, $returnValue)
    {
        $mock = $this->getMockBuilder('\DigitalOcean\Droplets\Droplets')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($this->any())
            ->method($method)
            ->will($this->returnValue($returnValue));

        return $mock;
    }

    /**
     * @return Images
     */
    protected function getMockImages($method, $returnValue)
    {
        $mock = $this->getMockBuilder('\DigitalOcean\Images\Images')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($this->any())
            ->method($method)
            ->will($this->returnValue($returnValue));

        return $mock;
    }

    /**
     * @return Regions
     */
    protected function getMockRegions($returnValue)
    {
        $mock = $this->getMockBuilder('\DigitalOcean\Regions\Regions')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($this->once())
            ->method('getAll')
            ->will($this->returnValue($returnValue));

        return $mock;
    }

    /**
     * @return Sizes
     */
    protected function getMockSizes($returnValue)
    {
        $mock = $this->getMockBuilder('\DigitalOcean\Sizes\Sizes')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($this->once())
            ->method('getAll')
            ->will($this->returnValue($returnValue));

        return $mock;
    }

    /**
     * @return SSHKeys
     */
    protected function getMockSSHKeys($method, $returnValue)
    {
        $mock = $this->getMockBuilder('\DigitalOcean\SSHKeys\SSHKeys')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($this->any())
            ->method($method)
            ->will($this->returnValue($returnValue));

        return $mock;
    }

    /**
     * @return Domains
     */
    protected function getMockDomains($method, $returnValue)
    {
        $mock = $this->getMockBuilder('\DigitalOcean\Domains\Domains')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($this->any())
            ->method($method)
            ->will($this->returnValue($returnValue));

        return $mock;
    }

    /**
     * @return DialogHelper
     */
    protected function getDialogAskConfirmation($returnValue)
    {
        $mock = $this->getMock('\Symfony\Component\Console\Helper\DialogHelper', array('askConfirmation'));
        $mock
            ->expects($this->at(0))
            ->method('askConfirmation')
            ->will($this->returnValue($returnValue));

        return $mock;
    }

    /**
     * @return DialogHelper
     */
    protected function getDialogAsk($returnValue)
    {
        $mock = $this->getMock('\Symfony\Component\Console\Helper\DialogHelper', array('ask'));
        $mock
            ->expects($this->once())
            ->method('ask')
            ->will($this->returnValue($returnValue));

        return $mock;
    }
}
