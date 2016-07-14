<?php

namespace Ddeboer\DataImport\Tests\ValueConverter;

use Ddeboer\DataImport\ValueConverter\DateTimeValueConverter;

class DateTimeValueConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertWithoutInputOrOutputFormatReturnsDateTimeInstance()
    {
        $value = '2011-10-20 13:05';
        $converter = new DateTimeValueConverter;
        $output = call_user_func($converter, $value);
        $this->assertInstanceOf('\DateTime', $output);
        $this->assertEquals('13', $output->format('H'));
    }

    public function testConvertWithFormatReturnsDateTimeInstance()
    {
        $value = '14/10/2008 09:40:20';
        $converter = new DateTimeValueConverter('d/m/Y H:i:s');
        $output = call_user_func($converter, $value);
        $this->assertInstanceOf('\DateTime', $output);
        $this->assertEquals('20', $output->format('s'));
    }

    public function testConvertWithInputAndOutputFormatReturnsString()
    {
        $value = '14/10/2008 09:40:20';
        $converter = new DateTimeValueConverter('d/m/Y H:i:s', 'd-M-Y');
        $output = call_user_func($converter, $value);
        $this->assertEquals('14-Oct-2008', $output);
    }

    public function testConvertWithNoInputStringWithOutputFormatReturnsString()
    {
        $value = '2011-10-20 13:05';
        $converter = new DateTimeValueConverter(null, 'd-M-Y');
        $output = call_user_func($converter, $value);
        $this->assertEquals('20-Oct-2011', $output);

    }

    public function testInvalidInputFormatThrowsException()
    {
        $value = '14/10/2008 09:40:20';
        $converter = new DateTimeValueConverter('d-m-y', 'd-M-Y');
        $this->setExpectedException("UnexpectedValueException", "14/10/2008 09:40:20 is not a valid date/time according to format d-m-y");
        call_user_func($converter, $value);
    }

    public function testNullIsReturnedIfNullPassed()
    {
        $converter = new DateTimeValueConverter('d-m-y', 'd-M-Y');
        $this->assertNull(call_user_func($converter, null));
    }
}
