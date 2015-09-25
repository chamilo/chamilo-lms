<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EasyExtendsBundle\Tests\Generator;

use Sonata\EasyExtendsBundle\Generator\Mustache;

class MustacheTest extends \PHPUnit_Framework_TestCase
{
    public function testMustache()
    {
        $this->assertEquals(Mustache::replace(" Hello {{ world }}", array(
          'world' => 'world'
        )), ' Hello world');

        $this->assertEquals(Mustache::replace(" Hello {{world}}", array(
          'world' => 'world'
        )), ' Hello world');

        $this->assertEquals(Mustache::replace(" Hello {{ world }}", array(
          'no-world' => 'world'
        )), ' Hello {{ world }}');

        $file = sprintf("%s/../fixtures/test.mustache", __DIR__);
        $this->assertEquals(Mustache::replaceFromFile($file, array(
          'world' => 'world'
        )), 'Hello world');
    }
}
