<?php
/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Test\Driver;

use PHPExiftool\Driver\TagFactory;

class TagFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TagFactory
     */
    protected $object;

    /**
     * @covers \PHPExiftool\Driver\TagFactory::GetFromRDFTagname
     * @covers \PHPExiftool\Driver\TagFactory::classnameFromTagname
     */
    public function testGetFromRDFTagname()
    {
        $tag = TagFactory::getFromRDFTagname('IPTC:SupplementalCategories');
        $this->assertInstanceOf('\PHPExiftool\Driver\Tag\IPTC\SupplementalCategories', $tag);

        $tag = TagFactory::getFromRDFTagname('XMPExif:ApertureValue');
        $this->assertInstanceOf('\PHPExiftool\Driver\Tag\XMPExif\ApertureValue', $tag);

        try {
            $tag = TagFactory::getFromRDFTagname('XMPExif:AnunexistingTag');
            $this->fail('Should raise a TagUnknown exception');
        } catch (\PHPExiftool\Exception\TagUnknown $e) {

        }
    }

    /**
     * @covers \PHPExiftool\Driver\TagFactory::GetFromRDFTagname
     * @covers \PHPExiftool\Exception\TagUnknown
     * @expectedException \PHPExiftool\Exception\TagUnknown
     */
    public function testGetFromRDFTagnameFail()
    {
        TagFactory::getFromRDFTagname('XMPExif:AnunexistingTag');
    }

    /**
     * @covers \PHPExiftool\Driver\TagFactory::HasFromRDFTagname
     */
    public function testHasFromRDFTagname()
    {
        $this->assertTrue(TagFactory::hasFromRDFTagname('IPTC:SupplementalCategories'));
        $this->assertFalse(TagFactory::hasFromRDFTagname('XMPExif:AnunexistingTag'));
    }
}
