<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Tests\Entity;

use Sonata\CoreBundle\Test\EntityManagerMockFactory;
use Sonata\UserBundle\Entity\UserManager;

/**
 * Class UserManagerTest.
 */
class UserManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPager()
    {
        $self = $this;
        $this
            ->getUserManager(function ($qb) use ($self) {
                $qb->expects($self->never())->method('andWhere');
                $qb->expects($self->once())->method('orderBy')->with(
                    $self->equalTo('u.username'),
                    $self->equalTo('ASC')
                );
                $qb->expects($self->never())->method('setParameter');
                $qb->expects($self->never())->method('setParameters');
            })
            ->getPager(array(), 1);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid sort field 'invalid' in 'className' class
     */
    public function testGetPagerWithInvalidSort()
    {
        $self = $this;
        $this
            ->getUserManager(function ($qb) use ($self) {
                $qb->expects($self->never())->method('andWhere');
                $qb->expects($self->never())->method('orderBy');
                $qb->expects($self->never())->method('setParameters');
            })
            ->getPager(array(), 1, 10, array('invalid' => 'ASC'));
    }

    public function testGetPagerWithValidSortDesc()
    {
        $self = $this;
        $this
            ->getUserManager(function ($qb) use ($self) {
                $qb->expects($self->once())->method('andWhere')->with($self->equalTo('u.enabled = :enabled'));
                $qb->expects($self->once())->method('setParameter')->with(
                    $self->equalTo('enabled'),
                    $self->equalTo(true)
                );
                $qb->expects($self->once())->method('orderBy')->with(
                    $self->equalTo('u.email'),
                    $self->equalTo('DESC')
                );
            })
            ->getPager(array('enabled' => true), 1, 10, array('email' => 'DESC'));
    }

    public function testGetPagerWithEnabledUsers()
    {
        $self = $this;
        $this
            ->getUserManager(function ($qb) use ($self) {
                $qb->expects($self->once())->method('andWhere')->with($self->equalTo('u.enabled = :enabled'));
                $qb->expects($self->once())->method('setParameter')->with(
                    $self->equalTo('enabled'),
                    $self->equalTo(true)
                );
                $qb->expects($self->once())->method('orderBy')->with(
                    $self->equalTo('u.username'),
                    $self->equalTo('ASC')
                );
            })
            ->getPager(array('enabled' => true), 1);
    }

    public function testGetPagerWithDisabledUsers()
    {
        $self = $this;
        $this
            ->getUserManager(function ($qb) use ($self) {
                $qb->expects($self->once())->method('andWhere')->with($self->equalTo('u.enabled = :enabled'));
                $qb->expects($self->once())->method('setParameter')->with(
                    $self->equalTo('enabled'),
                    $self->equalTo(false)
                );
                $qb->expects($self->once())->method('orderBy')->with(
                    $self->equalTo('u.username'),
                    $self->equalTo('ASC')
                );
            })
            ->getPager(array('enabled' => false), 1);
    }

    public function testGetPagerWithLockedUsers()
    {
        $self = $this;
        $this
            ->getUserManager(function ($qb) use ($self) {
                $qb->expects($self->once())->method('andWhere')->with($self->equalTo('u.locked = :locked'));
                $qb->expects($self->once())->method('setParameter')->with(
                    $self->equalTo('locked'),
                    $self->equalTo(true)
                );
                $qb->expects($self->once())->method('orderBy')->with(
                    $self->equalTo('u.username'),
                    $self->equalTo('ASC')
                );
            })
            ->getPager(array('locked' => true), 1);
    }

    public function testGetPagerWithNonLockedUsers()
    {
        $self = $this;
        $this
            ->getUserManager(function ($qb) use ($self) {
                $qb->expects($self->once())->method('andWhere')->with($self->equalTo('u.locked = :locked'));
                $qb->expects($self->once())->method('orderBy')->with(
                    $self->equalTo('u.username'),
                    $self->equalTo('ASC')
                );
                $qb->expects($self->any())->method('setParameter')->with(
                    $self->equalTo('locked'),
                    $self->equalTo(false)
                );
            })
            ->getPager(array('locked' => false), 1);
    }

    public function testGetPagerWithDisabledAndNonLockedUsers()
    {
        $self = $this;
        $this
            ->getUserManager(function ($qb) use ($self) {
                $qb->expects($self->exactly(2))->method('andWhere')->withConsecutive(
                    array($self->equalTo('u.enabled = :enabled')),
                    array($self->equalTo('u.locked = :locked'))
                );
                $qb->expects($self->exactly(2))->method('setParameter')->withConsecutive(
                    array(
                        $self->equalTo('enabled'),
                        $self->equalTo(false),
                    ),
                    array(
                        $self->equalTo('locked'),
                        $self->equalTo(false),
                    )
                );
                $qb->expects($self->once())->method('orderBy')->with(
                    $self->equalTo('u.username'),
                    $self->equalTo('ASC')
                );
            })
            ->getPager(array('enabled' => false, 'locked' => false), 1);
    }

    public function testGetPagerWithEnabledAndNonLockedUsers()
    {
        $self = $this;
        $this
            ->getUserManager(function ($qb) use ($self) {
                $qb->expects($self->exactly(2))->method('andWhere')->withConsecutive(
                    array($self->equalTo('u.enabled = :enabled')),
                    array($self->equalTo('u.locked = :locked'))
                );
                $qb->expects($self->exactly(2))->method('setParameter')->withConsecutive(
                    array(
                        $self->equalTo('enabled'),
                        $self->equalTo(true),
                    ),
                    array(
                        $self->equalTo('locked'),
                        $self->equalTo(false),
                    )
                );
                $qb->expects($self->once())->method('orderBy')->with(
                    $self->equalTo('u.username'),
                    $self->equalTo('ASC')
                );
            })
            ->getPager(array('enabled' => true, 'locked' => false), 1);
    }

    public function testGetPagerWithEnabledAndLockedUsers()
    {
        $self = $this;
        $this
            ->getUserManager(function ($qb) use ($self) {
                $qb->expects($self->exactly(2))->method('andWhere')->withConsecutive(
                    array($self->equalTo('u.enabled = :enabled')),
                    array($self->equalTo('u.locked = :locked'))
                );
                $qb->expects($self->exactly(2))->method('setParameter')->withConsecutive(
                    array(
                        $self->equalTo('enabled'),
                        $self->equalTo(true),
                    ),
                    array(
                        $self->equalTo('locked'),
                        $self->equalTo(true),
                    )
                );
                $qb->expects($self->once())->method('orderBy')->with(
                    $self->equalTo('u.username'),
                    $self->equalTo('ASC')
                );
            })
            ->getPager(array('enabled' => true, 'locked' => true), 1);
    }

    public function testGetPagerWithDisabledAndLockedUsers()
    {
        $self = $this;
        $this
            ->getUserManager(function ($qb) use ($self) {
                $qb->expects($self->exactly(2))->method('andWhere')->withConsecutive(
                    array($self->equalTo('u.enabled = :enabled')),
                    array($self->equalTo('u.locked = :locked'))
                );
                $qb->expects($self->exactly(2))->method('setParameter')->withConsecutive(
                    array(
                        $self->equalTo('enabled'),
                        $self->equalTo(false),
                    ),
                    array(
                        $self->equalTo('locked'),
                        $self->equalTo(true),
                    )
                );
                $qb->expects($self->once())->method('orderBy')->with(
                    $self->equalTo('u.username'),
                    $self->equalTo('ASC')
                );
            })
            ->getPager(array('enabled' => false, 'locked' => true), 1);
    }

    protected function getUserManager($qbCallback)
    {
        $em = EntityManagerMockFactory::create($this, $qbCallback, array(
            'username',
            'email',
        ));

        $encoder = $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
        $canonicalizer = $this->getMock('FOS\UserBundle\Util\CanonicalizerInterface');

        return new UserManager($encoder, $canonicalizer, $canonicalizer, $em, 'Sonata\UserBundle\Entity\BaseUser');
    }
}
