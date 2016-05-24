<?php

namespace Ddeboer\DataImport\Tests\ItemConverter;

use Ddeboer\DataImport\ItemConverter\CallbackItemConverter;

/**
 * @author Miguel Ibero <miguel@ibero.me>
 */
class CallbackItemConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvert()
    {
        $callable = function (array $item) {
            foreach ($item as $k => $v) {
                if (preg_match('/^(.+)\.([a-z]{2})$/', $k, $m)) {
                    $item[$m[1]][$m[2]] = $v;
                    unset($item[$k]);
                }
            }

            return $item;
        };

        $item = array(
            'tags.es'  => 'prueba',
            'tags.en'  => 'test'
        );

        $expected = array(
            'tags' => array(
                'es' => 'prueba',
                'en' => 'test'
            )
        );

        $converter = new CallbackItemConverter($callable);
        $this->assertEquals($expected, $converter->convert($item));
    }
}
