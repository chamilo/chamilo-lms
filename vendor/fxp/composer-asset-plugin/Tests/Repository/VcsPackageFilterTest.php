<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Repository;

use Composer\Installer\InstallationManager;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Package;
use Composer\Package\RootPackageInterface;
use Composer\Repository\InstalledFilesystemRepository;
use Fxp\Composer\AssetPlugin\Package\Version\VersionParser;
use Fxp\Composer\AssetPlugin\Repository\VcsPackageFilter;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Tests of VCS Package Filter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class VcsPackageFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $package;

    /**
     * @var InstallationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $installationManager;

    /**
     * @var InstalledFilesystemRepository|\PHPUnit_Framework_MockObject_MockObject|null
     */
    protected $installedRepository;

    /**
     * @var AssetTypeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $assetType;

    /**
     * @var VcsPackageFilter
     */
    protected $filter;

    protected function setUp()
    {
        $this->package = $this->getMock('Composer\Package\RootPackageInterface');
        $this->assetType = $this->getMock('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface');

        $versionConverter = $this->getMock('Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface');
        $versionConverter->expects($this->any())
            ->method('convertVersion')
            ->will($this->returnCallback(function ($value) {
                return $value;
            }));
        $this->assetType->expects($this->any())
            ->method('getVersionConverter')
            ->will($this->returnValue($versionConverter));

        $this->installationManager = $this->getMockBuilder('Composer\Installer\InstallationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->installationManager->expects($this->any())
            ->method('isPackageInstalled')
            ->will($this->returnValue(true));
    }

    protected function tearDown()
    {
        $this->package = null;
        $this->installedRepository = null;
        $this->assetType = null;
        $this->filter = null;
    }

    public function getDataProvider()
    {
        $extraSkipPattern = array('asset-pattern-skip-version' => false);

        return array(
            array('acme/foobar', 'v1.0.0',        'stable', array(),                                    false),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0'),            true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0'),            true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0'),            true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0'),            true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'dev',    array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'dev',    array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'dev',    array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-alpha1', 'dev',    array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-patch1', 'dev',    array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'dev',    array('acme/foobar' => '>=1.0'),            false, $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0@stable'),     false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0@dev'),        false),
            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '>=1.0@stable'),     false),
            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '>=1.0@dev'),        false),
            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '>=1.0@stable'),     false),
            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '>=1.0@dev'),        false),
            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '>=1.0@stable'),     false),
            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '>=1.0@dev'),        false),

            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0@stable'),     true),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0@dev'),        false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '>=1.0@stable'),     true),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '>=1.0@dev'),        false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '>=1.0@stable'),     true),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '>=1.0@dev'),        false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '>=1.0@stable'),     true),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '>=1.0@dev'),        false),

            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0@beta'),        false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '>=1.0@beta'),        false),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '>=1.0@beta'),        false),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '>=1.0@beta'),        false),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '>=1.0@dev'),         false),

            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '>=1.0@dev'),         false),

            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@stable'),      true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@RC'),          true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@beta'),        true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@alpha'),       true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@alpha'),       true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@dev'),         true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@dev'),         false, $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@stable'),      true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@RC'),          true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@beta'),        true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@alpha'),       true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@alpha'),       true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@dev'),         true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@dev'),         false, $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@stable'),      true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@RC'),          true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@beta'),        true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@alpha'),       true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@alpha'),       true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@dev'),         true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@dev'),         false, $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@stable'),      true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@RC'),          true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@beta'),        true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@alpha'),       true),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@alpha'),       true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@dev'),         true),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@dev'),         false, $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '~1.0'),              false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '~1.0'),              true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '~1.0'),              true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '~1.0'),              true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '~1.0'),              true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '~1.0'),              true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0'),               true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0'),               true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0'),               true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0'),               true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0'),               true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '@stable'),           false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '@RC'),               false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '@beta'),             false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '@alpha'),            false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '@dev'),              false),

            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '@stable'),           true),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '@RC'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '@beta'),             false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '@alpha'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '@dev'),              false),

            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '@stable'),           true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '@RC'),               true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '@beta'),             false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '@alpha'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '@dev'),              false),

            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '@stable'),           true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '@RC'),               true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '@beta'),             true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '@alpha'),            false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '@dev'),              false),

            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@stable'),           true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@stable'),           true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@RC'),               true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@RC'),               true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@beta'),             true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@beta'),             true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@alpha'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@alpha'),            true,  $extraSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@dev'),              true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@dev'),              false, $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1'),           true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1'),         true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1'),        true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '1.0-RC1'),           false),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '1.0-beta1'),         true),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '1.0-alpha1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '1.0-patch1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '1.0-patch1'),        true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '1.0-RC1'),           false),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '1.0-beta1'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '1.0-alpha1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '1.0-patch1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '1.0-patch1'),        true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '1.0-RC1'),           false),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '1.0-beta1'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '1.0-alpha1'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '1.0-patch1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '1.0-patch1'),        true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'dev',    array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'dev',    array('acme/foobar' => '1.0-RC1'),           false),
            array('acme/foobar', 'v1.0.0-beta1',  'dev',    array('acme/foobar' => '1.0-beta1'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'dev',    array('acme/foobar' => '1.0-alpha1'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'dev',    array('acme/foobar' => '1.0-patch1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'dev',    array('acme/foobar' => '1.0-patch1'),        false, $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@stable'),        false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@stable'),    true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@stable'),  true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@stable'), true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@stable'), true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@stable'), true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@RC'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@RC'),        false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@RC'),      true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@RC'),     true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@RC'),     true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@RC'),     true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@beta'),          false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@beta'),      false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@beta'),    false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@beta'),   true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@beta'),   true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@beta'),   true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@alpha'),         false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@alpha'),     false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@alpha'),   false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@alpha'),  false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@alpha'),  true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@alpha'),  true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@dev'),           false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@dev'),       false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@dev'),     false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@dev'),    false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@dev'),    true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@dev'),    false,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false, $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    true,  $extraSkipPattern),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0@dev|1.0.*@RC'),  false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   false, $extraSkipPattern),

            array('acme/foobar', 'standard/1.0.0', 'stable', array('acme/foobar' => '>=1.0'),            true),
        );
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param string $packageName
     * @param string $version
     * @param string $minimumStability
     * @param array  $rootRequires
     * @param bool   $validSkip
     * @param array  $rootExtra
     */
    public function testSkipVersion($packageName, $version, $minimumStability, array $rootRequires, $validSkip, array $rootExtra = array())
    {
        $this->init($rootRequires, $minimumStability, $rootExtra);

        $this->assertSame($validSkip, $this->filter->skip($this->assetType, $packageName, $version));
    }

    public function getDataProviderForDisableTest()
    {
        return array(
            array('acme/foobar', 'v1.0.0',        'stable', array(),                         false),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0'), false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0'), false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0'), false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0'), false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0'), true),
        );
    }

    /**
     * @dataProvider getDataProviderForDisableTest
     *
     * @param $packageName
     * @param $version
     * @param $minimumStability
     * @param array $rootRequires
     * @param $validSkip
     */
    public function testDisabledFilterWithInstalledPackage($packageName, $version, $minimumStability, array $rootRequires, $validSkip)
    {
        $this->init($rootRequires, $minimumStability);
        $this->filter->setEnabled(false);

        $this->assertSame($validSkip, $this->filter->skip($this->assetType, $packageName, $version));
    }

    public function getDataForInstalledTests()
    {
        $optn = 'asset-optimize-with-installed-packages';
        $optn2 = 'asset-optimize-with-conjunctive';

        $opt1 = array();
        $opt2 = array($optn => true, $optn2 => true);
        $opt3 = array($optn => false, $optn2 => true);
        $opt4 = array($optn => true, $optn2 => false);

        return array(
            array($opt1, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', '1.0.0', true),
            array($opt2, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', '1.0.0', true),
            array($opt3, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', '1.0.0', false),
            array($opt4, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', '1.0.0', false),
            array($opt1, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', '1.0.0', true),
            array($opt2, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', '1.0.0', true),
            array($opt3, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', '1.0.0', false),
            array($opt4, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', '1.0.0', false),

            array($opt1, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', null,    false),
            array($opt2, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', null,    false),
            array($opt3, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', null,    false),
            array($opt4, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', null,    false),
            array($opt1, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', null,    false),
            array($opt2, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', null,    false),
            array($opt3, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', null,    false),
            array($opt4, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', null,    false),

            array($opt1, 'acme/foobar', 'v1.0.0', 'stable', null,    '1.0.0', true),
            array($opt2, 'acme/foobar', 'v1.0.0', 'stable', null,    '1.0.0', true),
            array($opt3, 'acme/foobar', 'v1.0.0', 'stable', null,    '1.0.0', false),
            array($opt4, 'acme/foobar', 'v1.0.0', 'stable', null,    '1.0.0', true),
            array($opt1, 'acme/foobar', 'v0.9.0', 'stable', null,    '1.0.0', true),
            array($opt2, 'acme/foobar', 'v0.9.0', 'stable', null,    '1.0.0', true),
            array($opt3, 'acme/foobar', 'v0.9.0', 'stable', null,    '1.0.0', false),
            array($opt4, 'acme/foobar', 'v0.9.0', 'stable', null,    '1.0.0', true),

            array($opt1, 'acme/foobar', 'v1.0.0', 'stable', null,    null,    false),
            array($opt2, 'acme/foobar', 'v1.0.0', 'stable', null,    null,    false),
            array($opt3, 'acme/foobar', 'v1.0.0', 'stable', null,    null,    false),
            array($opt4, 'acme/foobar', 'v1.0.0', 'stable', null,    null,    false),
            array($opt1, 'acme/foobar', 'v0.9.0', 'stable', null,    null,    false),
            array($opt2, 'acme/foobar', 'v0.9.0', 'stable', null,    null,    false),
            array($opt3, 'acme/foobar', 'v0.9.0', 'stable', null,    null,    false),
            array($opt4, 'acme/foobar', 'v0.9.0', 'stable', null,    null,    false),

            array($opt1, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', '1.0.0', true),
            array($opt2, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', '1.0.0', true),
            array($opt3, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', '1.0.0', false),
            array($opt4, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', '1.0.0', false),
            array($opt1, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', '1.0.0', true),
            array($opt2, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', '1.0.0', true),
            array($opt3, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', '1.0.0', false),
            array($opt4, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', '1.0.0', false),

            array($opt1, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt2, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt3, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt4, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt1, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt2, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt3, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt4, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', null,    false),
        );
    }

    /**
     * @dataProvider getDataForInstalledTests
     *
     * @param array       $extra
     * @param string      $packageName
     * @param string      $version
     * @param string      $minimumStability
     * @param string|null $rootRequireVersion
     * @param string|null $installedVersion
     * @param bool        $validSkip
     */
    public function testFilterWithInstalledPackage(array $extra, $packageName, $version, $minimumStability, $rootRequireVersion, $installedVersion, $validSkip)
    {
        $installed = null === $installedVersion
            ? array()
            : array($packageName => $installedVersion);

        $require = null === $rootRequireVersion
            ? array()
            : array($packageName => $rootRequireVersion);

        $this->installedRepository = $this->getMockBuilder('Composer\Repository\InstalledFilesystemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->installedRepository->expects($this->any())
            ->method('getPackages')
            ->will($this->returnValue($this->convertInstalled($installed)));

        $this->init($require, $minimumStability, $extra);

        $this->assertSame($validSkip, $this->filter->skip($this->assetType, $packageName, $version));
    }

    /**
     * Init test.
     *
     * @param array  $requires
     * @param string $minimumStability
     * @param array  $extra
     */
    protected function init(array $requires = array(), $minimumStability = 'stable', array $extra = array())
    {
        $parser = new ArrayLoader();
        $linkRequires = $parser->parseLinks('__ROOT__', '1.0.0', 'requires', $requires);

        $stabilityFlags = $this->findStabilityFlags($requires);

        $this->package->expects($this->any())
            ->method('getRequires')
            ->will($this->returnValue($linkRequires));
        $this->package->expects($this->any())
            ->method('getDevRequires')
            ->will($this->returnValue(array()));
        $this->package->expects($this->any())
            ->method('getMinimumStability')
            ->will($this->returnValue($minimumStability));
        $this->package->expects($this->any())
            ->method('getStabilityFlags')
            ->will($this->returnValue($stabilityFlags));
        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue($extra));

        /* @var RootPackageInterface $package */
        $package = $this->package;
        $this->filter = new VcsPackageFilter($package, $this->installationManager, $this->installedRepository);
    }

    /**
     * Convert the installed package data tests to mock package instance.
     *
     * @param array $installed The config of installed packages
     *
     * @return array The package instance of installed packages
     */
    protected function convertInstalled(array $installed)
    {
        $packages = array();
        $parser = new VersionParser();

        foreach ($installed as $name => $version) {
            $package = $this->getMock('Composer\Package\PackageInterface');

            $package->expects($this->any())
                ->method('getName')
                ->will($this->returnValue($name));

            $package->expects($this->any())
                ->method('getVersion')
                ->will($this->returnValue($parser->normalize($version)));

            $package->expects($this->any())
                ->method('getPrettyVersion')
                ->will($this->returnValue($version));

            $packages[] = $package;
        }

        return $packages;
    }

    /**
     * Find the stability flag of requires.
     *
     * @param array $requires The require dependencies
     *
     * @return array
     */
    protected function findStabilityFlags(array $requires)
    {
        $flags = array();
        $stabilities = Package::$stabilities;

        foreach ($requires as $require => $prettyConstraint) {
            if (preg_match_all('/@('.implode('|', array_keys($stabilities)).')/', $prettyConstraint, $matches)) {
                $flags[$require] = $stabilities[$matches[1][0]];
            }
        }

        return $flags;
    }
}
