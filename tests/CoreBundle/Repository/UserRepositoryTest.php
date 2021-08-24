<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * @covers \UserRepository
 */
class UserRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreateUser(): void
    {
        self::bootKernel();
        //$em = self::getContainer()->get('doctrine')->getManager();

        $userRepo = self::getContainer()->get(UserRepository::class);
        $student = $this->createUser('student');
        $this->assertHasNoEntityViolations($student);

        $this->assertSame(1, \count($student->getRoles()));
        $this->assertTrue(\in_array('ROLE_USER', $student->getRoles(), true));

        $student->addRole('ROLE_STUDENT');
        $userRepo->update($student);

        $this->assertSame(2, \count($student->getRoles()));

        $student->addRole('ROLE_STUDENT');
        $userRepo->update($student);

        $this->assertSame(2, \count($student->getRoles()));
    }
}
