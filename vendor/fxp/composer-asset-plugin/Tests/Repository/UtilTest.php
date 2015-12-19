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

use Fxp\Composer\AssetPlugin\Repository\Util;

/**
 * Repository Util Tests.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class UtilTest extends \PHPUnit_Framework_TestCase
{
    public function getPackageNames()
    {
        return array(
            array('vendor/package',        'vendor/package'),
            array('vendor/package-name',   'vendor/package-name'),
            array('vendor/package_name',   'vendor/package_name'),
            array('vendor/package-1',      'vendor/package-1'),
            array('vendor/package_1',      'vendor/package_1'),
            array('vendor/package-name-1', 'vendor/package-name-1'),
            array('vendor/package_name_1', 'vendor/package_name_1'),
            array('vendor/package-1.0',    'vendor/package'),
            array('vendor/package-1.x',    'vendor/package'),
            array('vendor/package-1.X',    'vendor/package'),
            array('vendor/package-1.0.0',  'vendor/package'),
            array('vendor/package-1.0.x',  'vendor/package'),
            array('vendor/package-1.0.X',  'vendor/package'),

            array('vendor-name/package',        'vendor-name/package'),
            array('vendor-name/package-name',   'vendor-name/package-name'),
            array('vendor-name/package-1',      'vendor-name/package-1'),
            array('vendor-name/package-name-1', 'vendor-name/package-name-1'),
            array('vendor-name/package-1.0',    'vendor-name/package'),
            array('vendor-name/package-1.x',    'vendor-name/package'),
            array('vendor-name/package-1.X',    'vendor-name/package'),
            array('vendor-name/package-1.0.0',  'vendor-name/package'),
            array('vendor-name/package-1.0.x',  'vendor-name/package'),
            array('vendor-name/package-1.0.X',  'vendor-name/package'),

            array('vendor_name/package',        'vendor_name/package'),
            array('vendor_name/package-name',   'vendor_name/package-name'),
            array('vendor_name/package-1',      'vendor_name/package-1'),
            array('vendor_name/package-name-1', 'vendor_name/package-name-1'),
            array('vendor_name/package-1.0',    'vendor_name/package'),
            array('vendor_name/package-1.x',    'vendor_name/package'),
            array('vendor_name/package-1.X',    'vendor_name/package'),
            array('vendor_name/package-1.0.0',  'vendor_name/package'),
            array('vendor_name/package-1.0.x',  'vendor_name/package'),
            array('vendor_name/package-1.0.X',  'vendor_name/package'),
        );
    }

    /**
     * @dataProvider getPackageNames
     *
     * @param string $name
     * @param string $validName
     */
    public function testConvertAliasName($name, $validName)
    {
        $this->assertSame($validName, Util::convertAliasName($name));
    }
}
