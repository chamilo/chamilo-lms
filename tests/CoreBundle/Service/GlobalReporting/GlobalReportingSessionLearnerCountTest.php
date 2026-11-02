<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\GlobalReporting;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingContext;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingSectionQueryService;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The "Number of learners by session" column of /reporting/sessions replaces the legacy
 * my_space/session.php grid, which counted SessionManager::get_users_by_session($id, 0, true):
 * learners subscribed to the session itself, whether or not they have a course yet, and never
 * soft-deleted accounts. Counting course subscriptions instead hides learners still waiting for
 * a course, so the coach sees a smaller session than the one they manage.
 */
final class GlobalReportingSessionLearnerCountTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testCountsSessionLearnersLikeTheLegacyTrackingGrid(): void
    {
        self::bootKernel();
        $em = $this->getEntityManager();

        $title = 'learner-count-'.uniqid();
        $session = $this->createSession($title);
        $course = $this->createCourse($title);
        $session->addCourse($course);

        $withoutCourse = $this->createUser($title.'-a');
        $withCourse = $this->createUser($title.'-b');
        $softDeleted = $this->createUser($title.'-c');
        $softDeleted->setActive(User::SOFT_DELETED);

        $session
            ->addUserInSession(Session::STUDENT, $withoutCourse)
            ->addUserInSession(Session::STUDENT, $withCourse)
            ->addUserInSession(Session::STUDENT, $softDeleted)
        ;
        $session->addUserInCourse(Session::STUDENT, $withCourse, $course);
        $em->flush();

        $admin = $this->getUser('admin');
        $result = self::getContainer()->get(GlobalReportingSectionQueryService::class)->getSection(
            $this->context($admin, (int) $this->getAccessUrl()->getId()),
            'sessions',
            ['keyword' => $title],
        );

        $this->assertSame(1, $result['total']);
        $this->assertSame(2, $result['items'][0]['learners']);
    }

    private function context(User $user, int $accessUrlId): GlobalReportingContext
    {
        return new GlobalReportingContext(
            $user,
            $accessUrlId,
            true,
            false,
            false,
            true,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
        );
    }
}
