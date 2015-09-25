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

use Fxp\Composer\AssetPlugin\Converter\BowerPackageConverter;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Tests of bower package converter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BowerPackageConverterTest extends AbstractPackageConverterTest
{
    protected function setUp()
    {
        parent::setUp();

        /* @var AssetTypeInterface $type */
        $type = $this->type;
        $this->converter = new BowerPackageConverter($type);
        $this->asset = (array) json_decode(file_get_contents(__DIR__.'/../Fixtures/package/bower.json'), true);
    }

    public function testConvert()
    {
        $composer = $this->converter->convert($this->asset);

        $this->assertArrayHasKey('name', $composer);
        $this->assertSame('ASSET/'.$this->asset['name'], $composer['name']);

        $this->assertArrayHasKey('type', $composer);
        $this->assertSame('ASSET_TYPE', $composer['type']);

        $this->assertArrayHasKey('description', $composer);
        $this->assertSame($this->asset['description'], $composer['description']);

        $this->assertArrayHasKey('version', $composer);
        $this->assertSame('1.0.0-pre', $composer['version']);

        $this->assertArrayHasKey('keywords', $composer);
        $this->assertSame($this->asset['keywords'], $composer['keywords']);

        $this->assertArrayHasKey('require', $composer);
        $this->assertSame(array(
            'ASSET/library1' => '>= 1.0.0',
            'ASSET/library2' => '>= 1.0.0',
            'ASSET/library2-0.9.0' => '0.9.0',
            'ASSET/library3' => '*',
            'ASSET/library4' => '1.2.3',
            'ASSET/library5' => 'dev-default#0a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b',
            'ASSET/library6' => 'dev-branch',
            'ASSET/library7' => 'dev-1.2.* || 1.2.*',
            'ASSET/library8' => 'dev-1.2.x || 1.2.x',
            'ASSET/library9' => 'dev-master',
            'ASSET/library10' => '1.0.0',
            'ASSET/library11' => '*',
            'ASSET/library12' => '>=1 <2',
            'ASSET/library13' => '>=1 <2',
            'ASSET/library14' => '*',
            'ASSET/library15' => '*',
            'ASSET/library16' => '>=1 <2',
            'ASSET/test-library17-file' => '*',
            'ASSET/test-library18-file' => '1.2.3',
            'ASSET/test-library19-file' => '*',
        ), $composer['require']);

        $this->assertArrayHasKey('require-dev', $composer);
        $this->assertSame(array(
            'ASSET/dev-library1' => '>= 1.0.0',
            'ASSET/dev-library2' => '>= 1.0.0',
            'ASSET/dev-library2-0.9.0' => '0.9.0',
        ), $composer['require-dev']);

        $this->assertArrayHasKey('license', $composer);
        $this->assertSame($this->asset['license'], $composer['license']);

        $this->assertArrayHasKey('bin', $composer);
        $this->assertSame($this->asset['bin'], $composer['bin']);

        $this->assertArrayHasKey('extra', $composer);

        $this->assertArrayHasKey('bower-asset-main', $composer['extra']);
        $this->assertSame($this->asset['main'], $composer['extra']['bower-asset-main']);

        $this->assertArrayHasKey('bower-asset-ignore', $composer['extra']);
        $this->assertSame($this->asset['ignore'], $composer['extra']['bower-asset-ignore']);

        $this->assertArrayHasKey('bower-asset-private', $composer['extra']);
        $this->assertSame($this->asset['private'], $composer['extra']['bower-asset-private']);

        $this->assertArrayNotHasKey('homepage', $composer);
        $this->assertArrayNotHasKey('time', $composer);
        $this->assertArrayNotHasKey('authors', $composer);
        $this->assertArrayNotHasKey('support', $composer);
        $this->assertArrayNotHasKey('conflict', $composer);
        $this->assertArrayNotHasKey('replace', $composer);
        $this->assertArrayNotHasKey('provide', $composer);
        $this->assertArrayNotHasKey('suggest', $composer);
        $this->assertArrayNotHasKey('autoload', $composer);
        $this->assertArrayNotHasKey('autoload-dev', $composer);
        $this->assertArrayNotHasKey('include-path', $composer);
        $this->assertArrayNotHasKey('target-dir', $composer);
        $this->assertArrayNotHasKey('archive', $composer);
    }
}
