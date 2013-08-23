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

use Symfony\Component\Icu\IcuData;
use Symfony\Component\Icu\IcuRegionBundle;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class IcuRegionBundleTest extends IcuTestCase
{
    /**
     * @var string
     */
    private $resDir;

    /**
     * @var IcuRegionBundle
     */
    private $bundle;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $reader;

    protected function setUp()
    {
        parent::setUp();

        $this->resDir = IcuData::getResourceDirectory() . '/region';
        $this->reader = $this->getMock('Symfony\Component\Intl\ResourceBundle\Reader\StructuredBundleReaderInterface');
        $this->bundle = new IcuRegionBundle($this->reader);
    }

    public function testGetCountryNameOfUnknownCountry()
    {
        $this->reader->expects($this->never())
            ->method('readEntry');

        $this->assertNull($this->bundle->getCountryName('ZZ', 'en'));
    }

    public function testGetCountryNameOfNumericalRegion()
    {
        $this->reader->expects($this->never())
            ->method('readEntry');

        $this->assertNull($this->bundle->getCountryName(123, 'en'));
    }

    public function testGetCountryNameOfNumericalRegionWithLeadingZero()
    {
        $this->reader->expects($this->never())
            ->method('readEntry');

        $this->assertNull($this->bundle->getCountryName('010', 'en'));
    }

    public function testGetCountryNames()
    {
        $countries = array(
            'DE' => 'Germany',
            'AT' => 'Austria',
            'ZZ' => 'Unknown Country',
            '010' => 'Europe',
            110 => 'America',
        );

        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'en', array('Countries'))
            ->will($this->returnValue($countries));

        $sortedCountries = array(
            'AT' => 'Austria',
            'DE' => 'Germany',
        );

        $this->assertSame($sortedCountries, $this->bundle->getCountryNames('en'));
    }
}
