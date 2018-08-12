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

use Symfony\Component\Finder\Finder;

class TagTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Tag
     */
    protected $object;

    /**
     * @covers \PHPExiftool\Driver\AbstractTag::getDescription
     * @covers \PHPExiftool\Driver\AbstractTag::getGroupName
     * @covers \PHPExiftool\Driver\AbstractTag::getName
     * @covers \PHPExiftool\Driver\AbstractTag::getTagname
     * @covers \PHPExiftool\Driver\AbstractTag::getId
     * @covers \PHPExiftool\Driver\AbstractTag::getValues
     * @covers \PHPExiftool\Driver\AbstractTag::isMulti
     * @covers \PHPExiftool\Driver\AbstractTag::isWritable
     * @covers \PHPExiftool\Driver\AbstractTag::isBinary
     */
    public function testConsistency()
    {return;
        $finder = new Finder();
        $finder->files()->in(array(__DIR__ . '/../../../../../lib/PHPExiftool/Driver/Tag/'));

        foreach ($finder as $file) {
            $classname = substr(
                    str_replace(
                            array(realpath(__DIR__ . '/../../../../../lib'), '/')
                            , array('', '\\')
                            , $file->getRealPath()
                    ), 0, -4);

            $tag = new $classname;

            /* @var $tag \PHPExiftool\Driver\Tag */

            $this->assertTrue(is_scalar($tag->getDescription()));
            $this->assertTrue(is_scalar($tag->getGroupName()));
            $this->assertTrue(is_scalar($tag->getName()));
            $this->assertTrue(is_scalar($tag->getTagname()));
            $this->assertTrue(is_scalar($tag->getId()));

            if ($tag->getValues() !== null)
                $this->assertTrue(is_array($tag->getValues()));

            if ($tag->isMulti())
                $this->assertTrue($tag->isMulti());
            else
                $this->assertFalse($tag->isMulti());

            if ($tag->isWritable())
                $this->assertTrue($tag->isWritable());
            else
                $this->assertFalse($tag->isWritable(), $tag->getTagname() . " is writable");

            if ($tag->isBinary())
                $this->assertTrue($tag->isBinary());
            else
                $this->assertFalse($tag->isBinary());

            $tag->getMaxLength();

            $this->assertEquals(0, $tag->getMinLength());

            unset($tag);
        }
    }

}
