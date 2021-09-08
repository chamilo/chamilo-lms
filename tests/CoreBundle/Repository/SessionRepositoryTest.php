<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class SessionRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(SessionRepository::class);

        $url = $this->getAccessUrl();
        $coach = $this->createUser('coach');

        $session = (new Session())
            ->setName('session 1')
            ->setGeneralCoach($coach)
            ->addAccessUrl($url)

            ->setDuration(100)
            ->setShowDescription(true)
            ->setDescription('desc')
            ->setNbrClasses(0)
            ->setNbrUsers(0)
            ->setNbrCourses(0)
            ->setVisibility(Session::INVISIBLE)
        ;
        $this->assertHasNoEntityViolations($session);
        $em->persist($session);
        $em->flush();

        $this->assertSame(1, $repo->count([]));

        $this->assertSame(0, \count($session->getAllUsersFromCourse(0)));

        $this->assertTrue($session->isActiveForStudent());

        $this->assertTrue($session->isActiveForCoach());
        $this->assertFalse($session->isCurrentlyAccessible());

        $user = $this->createUser('test');
        $this->assertFalse($session->isUserGeneralCoach($user));

        $this->assertIsArray(Session::getStatusList());
    }
}
