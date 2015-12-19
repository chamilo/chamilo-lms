<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Repository\Vcs;

use Fxp\Composer\AssetPlugin\Repository\Vcs\Util;
use Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriver;

/**
 * Tests of util.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class UtilTest extends \PHPUnit_Framework_TestCase
{
    public function getDataProvider()
    {
        return array(
            array('key'),
            array('key.subkey'),
            array('key.subkey.subsubkey'),
        );
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testAddComposerTimeWithSimpleKey($resourceKey)
    {
        $composer = array(
            'name' => 'test',
        );
        $driver = new MockVcsDriver();

        $value = null;
        $keys = explode('.', $resourceKey);
        $start = count($keys) - 1;

        for ($i = $start; $i >= 0; --$i) {
            if (null === $value) {
                $value = 'level '.$i;
            }

            $value = array($keys[$i] => $value);
        }

        $driver->contents = json_encode($value);
        $composerValid = array_merge($composer, array(
            'time' => 'level '.(count($keys) - 1),
        ));

        $composer = Util::addComposerTime($composer, $resourceKey, 'http://example.tld', $driver);

        $this->assertSame($composerValid, $composer);
    }
}
