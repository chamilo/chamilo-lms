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

use Fxp\Composer\AssetPlugin\Type\NpmAssetType;

/**
 * Tests of npm asset type.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class NpmAssetTypeTest extends AbstractAssetTypeTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->type = new NpmAssetType($this->packageConverter, $this->versionConverter);
    }

    public function testInformations()
    {
        $this->assertSame('npm', $this->type->getName());
        $this->assertSame('npm-asset', $this->type->getComposerVendorName());
        $this->assertSame('npm-asset-library', $this->type->getComposerType());
        $this->assertSame('package.json', $this->type->getFilename());
        $this->assertSame('npm-asset/foobar', $this->type->formatComposerName('foobar'));
        $this->assertSame('npm-asset/foobar', $this->type->formatComposerName('npm-asset/foobar'));
    }
}
