<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DatagridBundle\Tests\ProxyQuery;

/**
 * @author Romain Mouillard <romain.mouillard@gmail.com>
 */
class BaseProxyQueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test calling undefined method on proxy query object will also try it on its query builder.
     */
    public function testFallbackOnQuerybuilder()
    {
        if (!class_exists('Doctrine\ORM\Query')) {
            $this->markTestSkipped("Doctrine ORM doesn't seem to be installed");
        }

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilder->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('foobar'));

        $proxyQuery = $this->getMockBuilder('Sonata\DatagridBundle\ProxyQuery\BaseProxyQuery')
            ->setConstructorArgs(array($queryBuilder))
            ->getMockForAbstractClass();

        $this->assertEquals('foobar', $proxyQuery->getType());
    }
}
