<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Package;

use Composer\Package\CompletePackage;
use Fxp\Composer\AssetPlugin\Package\LazyCompletePackage;
use Fxp\Composer\AssetPlugin\Package\LazyPackageInterface;
use Fxp\Composer\AssetPlugin\Package\Loader\LazyLoaderInterface;

/**
 * Tests of lazy asset package loader.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class LazyCompletePackageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LazyPackageInterface
     */
    protected $package;

    protected function setUp()
    {
        $this->package = new LazyCompletePackage('foo', '1.0.0.0', '1.0');
    }

    protected function tearDown()
    {
        $this->package = null;
    }

    public function getConfigLazyLoader()
    {
        return array(
            array(null),
            array('lazy'),
            array('lazy-exception'),
        );
    }

    /**
     * @param string $lazyType
     *
     * @dataProvider getConfigLazyLoader
     */
    public function testMissingAssetType($lazyType)
    {
        if (null !== $lazyType) {
            $lp = 'lazy' === $lazyType
                ? new CompletePackage($this->package->getName(),
                    $this->package->getVersion(), $this->package->getPrettyVersion())
                : false;

            $loader = $this->getMock('Fxp\Composer\AssetPlugin\Package\Loader\LazyLoaderInterface');
            $loader
                ->expects($this->any())
                ->method('load')
                ->will($this->returnValue($lp));

            /* @var LazyLoaderInterface$loader */
            $this->package->setLoader($loader);
        }

        $this->assertSame('library', $this->package->getType());
        $this->assertSame(array(), $this->package->getTransportOptions());
        $this->assertNull($this->package->getTargetDir());
        $this->assertSame(array(), $this->package->getExtra());
        $this->assertSame(array(), $this->package->getBinaries());
        $this->assertNull($this->package->getInstallationSource());
        $this->assertNull($this->package->getSourceType());
        $this->assertNull($this->package->getSourceUrl());
        $this->assertNull($this->package->getSourceReference());
        $this->assertNull($this->package->getSourceMirrors());
        $this->assertSame(array(), $this->package->getSourceUrls());
        $this->assertNull($this->package->getDistType());
        $this->assertNull($this->package->getDistUrl());
        $this->assertNull($this->package->getDistReference());
        $this->assertNull($this->package->getDistSha1Checksum());
        $this->assertNull($this->package->getDistMirrors());
        $this->assertSame(array(), $this->package->getDistUrls());
        $this->assertNull($this->package->getReleaseDate());
        $this->assertSame(array(), $this->package->getRequires());
        $this->assertSame(array(), $this->package->getConflicts());
        $this->assertSame(array(), $this->package->getProvides());
        $this->assertSame(array(), $this->package->getReplaces());
        $this->assertSame(array(), $this->package->getDevRequires());
        $this->assertSame(array(), $this->package->getSuggests());
        $this->assertSame(array(), $this->package->getAutoload());
        $this->assertSame(array(), $this->package->getDevAutoload());
        $this->assertSame(array(), $this->package->getIncludePaths());
        $this->assertNull($this->package->getNotificationUrl());
        $this->assertSame(array(), $this->package->getArchiveExcludes());
        $this->assertSame(array(), $this->package->getScripts());
        $this->assertNull($this->package->getRepositories());
        $this->assertSame(array(), $this->package->getLicense());
        $this->assertNull($this->package->getKeywords());
        $this->assertNull($this->package->getAuthors());
        $this->assertNull($this->package->getDescription());
        $this->assertNull($this->package->getHomepage());
        $this->assertSame(array(), $this->package->getSupport());
    }
}
