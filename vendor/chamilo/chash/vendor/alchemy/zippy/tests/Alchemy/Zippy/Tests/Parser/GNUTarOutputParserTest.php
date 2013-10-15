<?php

namespace Alchemy\Zippy\Tests\Parser;

use Alchemy\Zippy\Parser\GNUTarOutputParser;
use Alchemy\Zippy\Tests\TestCase;

class GNUTarOutputParserTest extends TestCase
{
    public function testNewParser()
    {
        return new GNUTarOutputParser();
    }

    /**
     * @depends testNewParser
     */
    public function testParseFileListing($parser)
    {
        $current_timezone = ini_get('date.timezone');
        ini_set('date.timezone', 'UTC');

        $output = "drwxrwxrwx myself/user 0 2006-06-09 12:06 practice/
            -rw-rw-rw- myself/user 62373 2006-06-09 12:06 practice/blues
            -rw-rw-rw- myself/user 11481 2006-06-09 12:06 practice/folk
            -rw-rw-rw- myself/user 23152 2006-06-09 12:06 practice/jazz
            -rw-rw-rw- myself/user 10240 2006-06-09 12:06 practice/records";

        $members = $parser->parseFileListing($output);

        $this->assertEquals(5, count($members));

        foreach ($members as $member) {
            $this->assertTrue(is_array($member));
        }

        $memberDirectory = array_shift($members);

        $this->assertTrue($memberDirectory['is_dir']);
        $this->assertEquals('practice/', $memberDirectory['location']);
        $this->assertEquals(0, $memberDirectory['size']);
        $date = $memberDirectory['mtime'];
        $this->assertTrue($date instanceof \DateTime);
        $this->assertEquals('1149854760', $date->format("U"));

        $memberFile = array_pop($members);

        $this->assertFalse($memberFile['is_dir']);
        $this->assertEquals('practice/records', $memberFile['location']);
        $this->assertEquals(10240, $memberFile['size']);
        $date = $memberFile['mtime'];
        $this->assertTrue($date instanceof \DateTime);
        $this->assertEquals('1149854760', $date->format("U"));

        ini_set('date.timezone', $current_timezone);
    }

    /**
     * @depends testNewParser
     */
    public function testParseVersion($parser)
    {
        $this->assertEquals('2.8.3', $parser->parseInflatorVersion("bsdtar 2.8.3 - libarchive 2.8.3"));
    }
}
