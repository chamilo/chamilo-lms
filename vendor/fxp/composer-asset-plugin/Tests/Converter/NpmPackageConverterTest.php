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

use Fxp\Composer\AssetPlugin\Converter\NpmPackageConverter;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Tests of npm package converter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class NpmPackageConverterTest extends AbstractPackageConverterTest
{
    protected function setUp()
    {
        parent::setUp();

        /* @var AssetTypeInterface $type */
        $type = $this->type;
        $this->converter = new NpmPackageConverter($type);
        $this->asset = (array) json_decode(file_get_contents(__DIR__.'/../Fixtures/package/npm.json'), true);
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

        $this->assertArrayHasKey('homepage', $composer);
        $this->assertSame($this->asset['homepage'], $composer['homepage']);

        $this->assertArrayHasKey('license', $composer);
        $this->assertSame($this->asset['license'], $composer['license']);

        $this->assertArrayHasKey('authors', $composer);
        $this->assertSame(array_merge(array($this->asset['author']), $this->asset['contributors']), $composer['authors']);

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
            'ASSET/library20' => '1 || 2',
        ), $composer['require']);

        $this->assertArrayHasKey('require-dev', $composer);
        $validDevRequires = $composer['require-dev'];
        unset($validDevRequires['ASSET/library3']);
        $this->assertSame(array(
            'ASSET/dev-library1' => '>= 1.0.0',
            'ASSET/dev-library2' => '>= 1.0.0',
            'ASSET/dev-library2-0.9.0' => '0.9.0',
        ), $validDevRequires);

        $this->assertArrayHasKey('bin', $composer);
        $this->assertTrue(is_array($composer['bin']));
        $this->assertSame($this->asset['bin'], $composer['bin'][0]);

        $this->assertArrayHasKey('extra', $composer);

        $this->assertArrayHasKey('npm-asset-bugs', $composer['extra']);
        $this->assertSame($this->asset['bugs'], $composer['extra']['npm-asset-bugs']);

        $this->assertArrayHasKey('npm-asset-files', $composer['extra']);
        $this->assertSame($this->asset['files'], $composer['extra']['npm-asset-files']);

        $this->assertArrayHasKey('npm-asset-main', $composer['extra']);
        $this->assertSame($this->asset['main'], $composer['extra']['npm-asset-main']);

        $this->assertArrayHasKey('npm-asset-man', $composer['extra']);
        $this->assertSame($this->asset['man'], $composer['extra']['npm-asset-man']);

        $this->assertArrayHasKey('npm-asset-directories', $composer['extra']);
        $this->assertSame($this->asset['directories'], $composer['extra']['npm-asset-directories']);

        $this->assertArrayHasKey('npm-asset-repository', $composer['extra']);
        $this->assertSame($this->asset['repository'], $composer['extra']['npm-asset-repository']);

        $this->assertArrayHasKey('npm-asset-scripts', $composer['extra']);
        $this->assertSame($this->asset['scripts'], $composer['extra']['npm-asset-scripts']);

        $this->assertArrayHasKey('npm-asset-config', $composer['extra']);
        $this->assertSame($this->asset['config'], $composer['extra']['npm-asset-config']);

        $this->assertArrayHasKey('npm-asset-bundled-dependencies', $composer['extra']);
        $this->assertSame($this->asset['bundledDependencies'], $composer['extra']['npm-asset-bundled-dependencies']);

        $this->assertArrayHasKey('npm-asset-optional-dependencies', $composer['extra']);
        $this->assertSame($this->asset['optionalDependencies'], $composer['extra']['npm-asset-optional-dependencies']);

        $this->assertArrayHasKey('npm-asset-engines', $composer['extra']);
        $this->assertSame($this->asset['engines'], $composer['extra']['npm-asset-engines']);

        $this->assertArrayHasKey('npm-asset-engine-strict', $composer['extra']);
        $this->assertSame($this->asset['engineStrict'], $composer['extra']['npm-asset-engine-strict']);

        $this->assertArrayHasKey('npm-asset-os', $composer['extra']);
        $this->assertSame($this->asset['os'], $composer['extra']['npm-asset-os']);

        $this->assertArrayHasKey('npm-asset-cpu', $composer['extra']);
        $this->assertSame($this->asset['cpu'], $composer['extra']['npm-asset-cpu']);

        $this->assertArrayHasKey('npm-asset-prefer-global', $composer['extra']);
        $this->assertSame($this->asset['preferGlobal'], $composer['extra']['npm-asset-prefer-global']);

        $this->assertArrayHasKey('npm-asset-private', $composer['extra']);
        $this->assertSame($this->asset['private'], $composer['extra']['npm-asset-private']);

        $this->assertArrayHasKey('npm-asset-publish-config', $composer['extra']);
        $this->assertSame($this->asset['publishConfig'], $composer['extra']['npm-asset-publish-config']);

        $this->assertArrayNotHasKey('time', $composer);
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
