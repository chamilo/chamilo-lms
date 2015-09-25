<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Type;

use Fxp\Composer\AssetPlugin\Converter\PackageConverterInterface;
use Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Abstract class for tests of asset type.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractAssetTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PackageConverterInterface
     */
    protected $packageConverter;

    /**
     * @var VersionConverterInterface
     */
    protected $versionConverter;

    /**
     * @var AssetTypeInterface
     */
    protected $type;

    protected function setUp()
    {
        $this->packageConverter = $this->getMock('Fxp\Composer\AssetPlugin\Converter\PackageConverterInterface');
        $this->versionConverter = $this->getMock('Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface');
    }

    protected function tearDown()
    {
        $this->packageConverter = null;
        $this->versionConverter = null;
        $this->type = null;
    }

    public function testConverter()
    {
        $this->assertInstanceOf('Fxp\Composer\AssetPlugin\Converter\PackageConverterInterface', $this->type->getPackageConverter());
        $this->assertInstanceOf('Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface', $this->type->getVersionConverter());
    }
}
