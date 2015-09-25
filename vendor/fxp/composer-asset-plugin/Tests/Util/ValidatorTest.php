<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Util;

use Fxp\Composer\AssetPlugin\Assets;
use Fxp\Composer\AssetPlugin\Util\Validator;

/**
 * Tests for the validator.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testValidBranch()
    {
        $this->assertNotFalse(Validator::validateBranch('master'));
    }

    public function testInvalidBranch()
    {
        $this->assertFalse(Validator::validateBranch('1.x'));
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function getAssetTypes()
    {
        return array(
            array('npm'),
            array('bower'),
        );
    }

    /**
     * @param $type
     *
     * @dataProvider getAssetTypes
     */
    public function testValidTag($type)
    {
        $assetType = Assets::createType($type);
        $this->assertNotFalse(Validator::validateTag('1.0.0', $assetType));
    }

    /**
     * @param $type
     *
     * @dataProvider getAssetTypes
     */
    public function testInvalidTag($type)
    {
        $assetType = Assets::createType($type);
        $this->assertFalse(Validator::validateTag('version', $assetType));
    }
}
