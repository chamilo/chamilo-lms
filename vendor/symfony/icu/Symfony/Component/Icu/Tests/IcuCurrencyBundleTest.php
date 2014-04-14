<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Icu\Tests;

use Symfony\Component\Icu\IcuCurrencyBundle;
use Symfony\Component\Icu\IcuData;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class IcuCurrencyBundleTest extends IcuTestCase
{
    /**
     * @var string
     */
    private $resDir;

    /**
     * @var IcuCurrencyBundle
     */
    private $bundle;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $reader;

    protected function setUp()
    {
        parent::setUp();

        $this->resDir = IcuData::getResourceDirectory() . '/curr';
        $this->reader = $this->getMock('Symfony\Component\Intl\ResourceBundle\Reader\StructuredBundleReaderInterface');
        $this->bundle = new IcuCurrencyBundle($this->reader);
    }

    public function testGetCurrencySymbol()
    {
        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'en', array('Currencies', 'EUR', 0))
            ->will($this->returnValue('€'));

        $this->assertSame('€', $this->bundle->getCurrencySymbol('EUR', 'en'));
    }

    public function testGetCurrencyName()
    {
        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'en', array('Currencies', 'EUR', 1))
            ->will($this->returnValue('Euro'));

        $this->assertSame('Euro', $this->bundle->getCurrencyName('EUR', 'en'));
    }

    public function testGetCurrencyNames()
    {
        $currencies = array(
            'EUR' => array(1 => 'Euro'),
            'USD' => array(1 => 'Dollar'),
        );

        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'en', array('Currencies'))
            ->will($this->returnValue($currencies));

        $sortedCurrencies = array(
            'USD' => 'Dollar',
            'EUR' => 'Euro',
        );

        $this->assertSame($sortedCurrencies, $this->bundle->getCurrencyNames('en'));
    }

    public function testGetFractionDigits()
    {
        $currencyData = array(
            'EUR' => array(0 => 123),
            'USD' => array(0 => 456),
        );

        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'misc', array('CurrencyMeta'))
            ->will($this->returnValue($currencyData));

        $this->assertSame(123, $this->bundle->getFractionDigits('EUR'));
    }

    public function testGetFractionDigitsFromDefaultBlock()
    {
        $currencyData = array(
            'USD' => array(0 => 456),
            'DEFAULT' => array(0 => 123),
        );

        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'misc', array('CurrencyMeta'))
            ->will($this->returnValue($currencyData));

        $this->assertSame(123, $this->bundle->getFractionDigits('EUR'));
    }

    public function testGetRoundingIncrement()
    {
        $currencyData = array(
            'EUR' => array(1 => 123),
            'USD' => array(1 => 456),
        );

        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'misc', array('CurrencyMeta'))
            ->will($this->returnValue($currencyData));

        $this->assertSame(123, $this->bundle->getRoundingIncrement('EUR'));
    }

    public function testGetRoundingIncrementFromDefaultBlock()
    {
        $currencyData = array(
            'USD' => array(1 => 456),
            'DEFAULT' => array(1 => 123),
        );

        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'misc', array('CurrencyMeta'))
            ->will($this->returnValue($currencyData));

        $this->assertSame(123, $this->bundle->getRoundingIncrement('EUR'));
    }
}
