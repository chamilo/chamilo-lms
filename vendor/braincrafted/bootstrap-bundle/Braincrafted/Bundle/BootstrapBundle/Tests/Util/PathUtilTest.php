<?php

namespace Braincrafted\Bundle\BootstrapBundle\Tests\Util;

use Braincrafted\Bundle\BootstrapBundle\Util\PathUtil;

/**
 * PathUtilTest
 *
 * @group unit
 */
class PathUtilTest extends \PHPUnit_Framework_TestCase
{
    /** @var PathUtil */
    private $util;

    public function setUp()
    {
        $this->util = new PathUtil;
    }

    /**
     * @param $expected string
     * @param $from     string
     * @param $to       string
     *
     * @covers Braincrafted\Bundle\BootstrapBundle\Util\PathUtil::getRelativePath()
     * @dataProvider relativePathProvider
     */
    public function testGetRelativePath($expected, $from, $to)
    {
        $this->assertEquals($expected, $this->util->getRelativePath($from, $to));
    }

    /**
     * @return array
     */
    public function relativePathProvider()
    {
        return array(
            array('', '/var/user', '/var/user'),
            array('./foo/a.php', '/var/user/a.php', '/var/user/foo/a.php'),
            array('../bar/a.php', '/var/user/foo/a.php', '/var/user/bar/a.php')
        );
    }
}
