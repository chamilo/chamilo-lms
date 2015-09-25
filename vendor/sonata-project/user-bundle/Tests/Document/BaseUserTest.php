<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Tests\Document;

use Sonata\UserBundle\Document\BaseUser;

class BaseUserTest extends \PHPUnit_Framework_TestCase
{
    public function testDateSetters()
    {
        // Given
        $user = new BaseUser();
        $today = new \DateTime();

        // When
        $user->setCreatedAt($today);
        $user->setUpdatedAt($today);
        $user->setCredentialsExpireAt($today);

        // Then
        $this->assertTrue($user->getCreatedAt() instanceof \DateTime, 'Should return a DateTime object');
        $this->assertEquals($today->format('U'), $user->getCreatedAt()->format('U') , 'Should contain today\'s date');

        $this->assertTrue($user->getUpdatedAt() instanceof \DateTime, 'Should return a DateTime object');
        $this->assertEquals($today->format('U'), $user->getUpdatedAt()->format('U') , 'Should contain today\'s date');

        $this->assertTrue($user->getCredentialsExpireAt() instanceof \DateTime, 'Should return a DateTime object');
        $this->assertEquals($today->format('U'), $user->getCredentialsExpireAt()->format('U') , 'Should contain today\'s date');
    }

    public function testDateWithPrePersist()
    {
        // Given
        $user = new BaseUser();
        $today = new \DateTime();

        // When
        $user->prePersist();

        // Then
        $this->assertTrue($user->getCreatedAt() instanceof \DateTime, 'Should contain a DateTime object');
        $this->assertEquals($today->format('Y-m-d'), $user->getUpdatedAt()->format('Y-m-d'), 'Should be created today');

        $this->assertTrue($user->getUpdatedAt() instanceof \DateTime, 'Should contain a DateTime object');
        $this->assertEquals($today->format('Y-m-d'), $user->getUpdatedAt()->format('Y-m-d'), 'Should be updated today');
    }

    public function testDateWithPreUpdate()
    {
        // Given
        $user = new BaseUser();
        $user->setCreatedAt( \DateTime::createFromFormat('Y-m-d', '2012-01-01'));
        $today = new \DateTime();

        // When
        $user->preUpdate();

        // Then
        $this->assertTrue($user->getCreatedAt() instanceof \DateTime, 'Should contain a DateTime object');
        $this->assertEquals('2012-01-01', $user->getCreatedAt()->format('Y-m-d'), 'Should be created at 2012-01-01.');

        $this->assertTrue($user->getUpdatedAt() instanceof \DateTime, 'Should contain a DateTime object');
        $this->assertEquals($today->format('Y-m-d'), $user->getUpdatedAt()->format('Y-m-d'), 'Should be updated today');
    }

    public function testSettingMultipleGroups()
    {
        // Given
        $user = new BaseUser();
        $group1 = $this->getMock('FOS\UserBundle\Model\GroupInterface');
        $group1->expects($this->any())->method('getName')->will($this->returnValue('Group 1'));
        $group2 = $this->getMock('FOS\UserBundle\Model\GroupInterface');
        $group2->expects($this->any())->method('getName')->will($this->returnValue('Group 2'));

        // When
        $user->setGroups(array($group1, $group2));

        // Then
        $this->assertCount(2, $user->getGroups(), 'Should have 2 groups');
        $this->assertTrue($user->hasGroup('Group 1'), 'Should have a group named "Group 1"');
        $this->assertTrue($user->hasGroup('Group 2'), 'Should have a group named "Group 2"');
    }

    public function testTwoStepVerificationCode()
    {
        // Given
        $user = new BaseUser();

        // When
        $user->setTwoStepVerificationCode('123456');

        // Then
        $this->assertEquals('123456', $user->getTwoStepVerificationCode(), 'Should return the two step verification code');
    }

    public function testToStringWithName()
    {
        // Given
        $user = new BaseUser();
        $user->setUsername('John');

        // When
        $string = (string) $user;

        // Then
        $this->assertEquals('John', $string, 'Should return the username as string representation');
    }

    public function testToStringWithoutName()
    {
        // Given
        $user = new BaseUser();

        // When
        $string = (string) $user;

        // Then
        $this->assertEquals('-', $string, 'Should return a string representation');
    }

}
