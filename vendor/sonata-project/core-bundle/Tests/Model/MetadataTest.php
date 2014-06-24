<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Sonata\CoreBundle\Tests\Model;

use Sonata\CoreBundle\Model\Metadata;


/**
 * Class MetadataTest
 *
 * @package Sonata\CoreBundle\Tests\Model
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class MetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $metadata = new Metadata("title", "description", "image", "domain", array('key1' => 'value1'));

        $this->assertEquals("title", $metadata->getTitle());
        $this->assertEquals("description", $metadata->getDescription());
        $this->assertEquals("image", $metadata->getImage());
        $this->assertEquals("domain", $metadata->getDomain());

        $this->assertEquals("value1", $metadata->getOption('key1'));
        $this->assertEquals("valueDefault", $metadata->getOption('none', 'valueDefault'));
        $this->assertNull($metadata->getOption('none'));
        $this->assertEquals(array('key1' => 'value1'), $metadata->getOptions());

        $metadata->setOption('key2', "value2");

        $this->assertEquals("value2", $metadata->getOption('key2'));
        $this->assertEquals(array('key1' => 'value1', 'key2' => 'value2'), $metadata->getOptions());

        $metadata2 = new Metadata("title", "description", "image");
        $this->assertNull($metadata2->getDomain());
        $this->assertEquals(array(), $metadata2->getOptions());
    }
}
