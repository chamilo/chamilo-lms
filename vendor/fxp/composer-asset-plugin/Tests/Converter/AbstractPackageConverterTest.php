<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Converter;

use Fxp\Composer\AssetPlugin\Converter\PackageConverterInterface;
use Fxp\Composer\AssetPlugin\Tests\Fixtures\Converter\InvalidPackageConverter;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Abstract tests of asset package converter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractPackageConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssetTypeInterface
     */
    protected $type;

    /**
     * @var PackageConverterInterface
     */
    protected $converter;

    /**
     * @var array
     */
    protected $asset;

    protected function setUp()
    {
        $versionConverter = $this->getMock('Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface');
        $versionConverter->expects($this->any())
            ->method('convertVersion')
            ->will($this->returnCallback(function ($value) {
                return $value;
            }));
        $versionConverter->expects($this->any())
            ->method('convertRange')
            ->will($this->returnCallback(function ($value) {
                return $value;
            }));
        $type = $this->getMock('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface');
        $type->expects($this->any())
            ->method('getComposerVendorName')
            ->will($this->returnValue('ASSET'));
        $type->expects($this->any())
            ->method('getComposerType')
            ->will($this->returnValue('ASSET_TYPE'));
        $type->expects($this->any())
            ->method('getVersionConverter')
            ->will($this->returnValue($versionConverter));
        $type->expects($this->any())
            ->method('formatComposerName')
            ->will($this->returnCallback(function ($value) {
                return 'ASSET/'.$value;
            }));

        $this->type = $type;
    }

    protected function tearDown()
    {
        $this->type = null;
        $this->converter = null;
        $this->asset = array();
    }

    public function testConversionWithInvalidKey()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->converter = new InvalidPackageConverter($this->type);

        $this->converter->convert(array(
            'name' => 'foo',
        ));
    }
}
