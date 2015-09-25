<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests;

use Fxp\Composer\AssetPlugin\Assets;

/**
 * Tests of assets factory.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class AssetsTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTypes()
    {
        $this->assertEquals(array(
            'npm',
            'bower',
        ), Assets::getTypes());
    }

    public function testGetRegistries()
    {
        $this->assertEquals(array(
            'npm',
            'bower',
        ), array_keys(Assets::getRegistries()));
    }

    public function testGetVcsRepositoryDrivers()
    {
        $this->assertEquals(array(
            'vcs',
            'github',
            'git-bitbucket',
            'git',
            'hg-bitbucket',
            'hg',
            'perforce',
            'svn',
        ), array_keys(Assets::getVcsRepositoryDrivers()));
    }

    public function testGetVcsDrivers()
    {
        $this->assertEquals(array(
            'github',
            'git-bitbucket',
            'git',
            'hg-bitbucket',
            'hg',
            'perforce',
            'svn',
        ), array_keys(Assets::getVcsDrivers()));
    }

    public function testCreationOfInvalidType()
    {
        $this->setExpectedException('InvalidArgumentException');

        Assets::createType(null);
    }

    public function testCreationOfNpmAsset()
    {
        $type = Assets::createType('npm');

        $this->assertInstanceOf('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface', $type);
    }

    public function testCreationOfBowerAsset()
    {
        $type = Assets::createType('bower');

        $this->assertInstanceOf('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface', $type);
    }
}
