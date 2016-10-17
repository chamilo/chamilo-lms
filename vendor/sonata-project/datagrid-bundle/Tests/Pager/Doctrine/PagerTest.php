<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DatagridBundle\Tests\Pager\Doctrine;

use Sonata\DatagridBundle\Pager\Doctrine\Pager;

/**
 * @author Romain Mouillard <romain.mouillard@gmail.com>
 */
class PagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Pager
     */
    private $pager;

    protected function setUp()
    {
        $this->pager = $this->getMockForAbstractClass('Sonata\DatagridBundle\Pager\Doctrine\Pager');

        if (!class_exists('Doctrine\ORM\Query')) {
            $this->markTestSkipped("Doctrine ORM doesn't seem to be installed");
        }
    }

    /**
     * Test get results method retuns query results.
     */
    public function testGetResults()
    {
        $query = $this->getMock('Sonata\DatagridBundle\ProxyQuery\ProxyQueryInterface');

        $object1      = new \stdClass();
        $object1->foo = 'bar1';

        $object2      = new \stdClass();
        $object2->foo = 'bar2';

        $object3      = new \stdClass();
        $object3->foo = 'bar3';

        $expectedObjects = array($object1, $object2, $object3);

        $query->expects($this->any())
            ->method('execute')
            ->will($this->returnValue($expectedObjects));

        $this->pager->setQuery($query);

        $this->assertEquals($expectedObjects, $this->pager->getResults());
    }
}
