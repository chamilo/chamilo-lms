<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * A manager role grants access to another user's tracking only for the users that
 * role actually covers: an HR manager must follow the target. A global ROLE_HR with
 * no relation to the target must not read their gradebook, progress or certificates.
 */
final class UserTrackingMetricApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const string URI = '/api/tracking/user_gradebook_result_in_course_and_session';

    public function testHrManagerCannotReadTrackingOfUnmanagedUser(): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Tracking Sec Course A');
        $target = $this->createUser('trk_sec_target_a');
        $hr = $this->createUser('trk_sec_hr_a', '', '', 'ROLE_HR');

        $course->addUserAsStudent($target);
        $course->addUserAsStudent($hr);
        $courseRepo->update($course);

        $this->createClientWithCredentials($this->getUserTokenFromUser($hr))->request(
            'GET',
            self::URI.'?userId='.$target->getId().'&courseId='.$course->getId()
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testHrManagerReadsTrackingOfManagedUser(): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Tracking Sec Course B');
        $target = $this->createUser('trk_sec_target_b');
        $hr = $this->createUser('trk_sec_hr_b', '', '', 'ROLE_HR');

        $course->addUserAsStudent($target);
        $course->addUserAsStudent($hr);
        $courseRepo->update($course);

        // The HR manager follows the target: user = target, friend = HR manager.
        $em = $this->getEntityManager();
        $em->persist(
            (new UserRelUser())
                ->setUser($target)
                ->setFriend($hr)
                ->setRelationType(UserRelUser::USER_RELATION_TYPE_HRM)
        );
        $em->flush();

        $this->createClientWithCredentials($this->getUserTokenFromUser($hr))->request(
            'GET',
            self::URI.'?userId='.$target->getId().'&courseId='.$course->getId()
        );

        $this->assertResponseStatusCodeSame(200);
    }
}
