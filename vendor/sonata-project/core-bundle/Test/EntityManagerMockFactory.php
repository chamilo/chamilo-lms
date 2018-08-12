<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Test;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Version;

class EntityManagerMockFactory
{
    /**
     * @param \PHPUnit_Framework_TestCase $test
     * @param \Closure                    $qbCallback
     * @param                             $fields
     *
     * @return EntityManagerInterface
     */
    public static function create(\PHPUnit_Framework_TestCase $test, \Closure $qbCallback, $fields)
    {
        $query = $test->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()->getMock();
        $query->expects($test->any())->method('execute')->will($test->returnValue(true));

        if (Version::compare('2.5.0') < 1) {
            $entityManager = $test->getMockBuilder('Doctrine\ORM\EntityManagerInterface')->getMock();
            $qb = $test->getMockBuilder('Doctrine\ORM\QueryBuilder')->setConstructorArgs(array($entityManager))->getMock();
        } else {
            $qb = $test->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        }

        $qb->expects($test->any())->method('select')->will($test->returnValue($qb));
        $qb->expects($test->any())->method('getQuery')->will($test->returnValue($query));
        $qb->expects($test->any())->method('where')->will($test->returnValue($qb));
        $qb->expects($test->any())->method('orderBy')->will($test->returnValue($qb));
        $qb->expects($test->any())->method('andWhere')->will($test->returnValue($qb));
        $qb->expects($test->any())->method('leftJoin')->will($test->returnValue($qb));

        $qbCallback($qb);

        $repository = $test->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $repository->expects($test->any())->method('createQueryBuilder')->will($test->returnValue($qb));

        $metadata = $test->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')->getMock();
        $metadata->expects($test->any())->method('getFieldNames')->will($test->returnValue($fields));
        $metadata->expects($test->any())->method('getName')->will($test->returnValue('className'));

        $em = $test->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($test->any())->method('getRepository')->will($test->returnValue($repository));
        $em->expects($test->any())->method('getClassMetadata')->will($test->returnValue($metadata));

        return $em;
    }
}
