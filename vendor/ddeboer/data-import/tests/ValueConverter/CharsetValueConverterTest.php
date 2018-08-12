<?php
namespace Ddeboer\DataImport\Tests\ValueConverter;

use Ddeboer\DataImport\ValueConverter\CharsetValueConverter;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class CharsetValueConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvert()
    {
        $utf8 = utf8_encode('test');

        $converter = new CharsetValueConverter('UTF-8');
        $this->assertEquals($utf8, call_user_func($converter, $utf8));

        $value = iconv('UTF-8', 'UTF-16', $utf8);
        $converter = new CharsetValueConverter('UTF-8', 'UTF-16');
        $this->assertEquals($utf8, call_user_func($converter, $value));
    }
}
