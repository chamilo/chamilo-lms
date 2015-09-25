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

use Fxp\Composer\AssetPlugin\Type\BowerAssetType;

/**
 * Tests of bower asset type.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BowerAssetTypeTest extends AbstractAssetTypeTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->type = new BowerAssetType($this->packageConverter, $this->versionConverter);
    }

    public function testInformations()
    {
        $this->assertSame('bower', $this->type->getName());
        $this->assertSame('bower-asset', $this->type->getComposerVendorName());
        $this->assertSame('bower-asset-library', $this->type->getComposerType());
        $this->assertSame('bower.json', $this->type->getFilename());
        $this->assertSame('bower-asset/foobar', $this->type->formatComposerName('foobar'));
        $this->assertSame('bower-asset/foobar', $this->type->formatComposerName('bower-asset/foobar'));
    }
}
