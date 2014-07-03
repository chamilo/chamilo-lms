<?php

namespace SimpleThings\EntityAudit\Tests\Utils;

use \SimpleThings\EntityAudit\Utils\SimpleDiff;

class SimpleDiffTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataDiff
     * @param $old
     * @param $new
     * @param $output
     * @return void
     */
    public function testDiff($old, $new, $output)
    {
        $diff = new SimpleDiff();
        $d = $diff->htmlDiff($old, $new);

        $this->assertEquals($output, $d);
    }

    static public function dataDiff()
    {
        return array(
            array('Foo', 'foo', '<del>Foo</del> <ins>foo</ins> '),
            array('Foo Foo', 'Foo', 'Foo <del>Foo</del> '),
            array('Foo', 'Foo Foo', 'Foo <ins>Foo</ins> '),
            array('Foo Bar Baz', 'Foo Foo Foo', 'Foo <del>Bar Baz</del> <ins>Foo Foo</ins> '),
            array('Foo Bar Baz', 'Foo Baz', 'Foo <del>Bar</del> Baz '),
        );
    }
}
