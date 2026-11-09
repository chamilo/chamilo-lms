<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\CourseUser;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * CourseUserManager::getListData() was rewritten to sort/filter/paginate the raw course-membership
 * rows *before* hydrating User entities, then batch-fetch groups, extra-field values and illustration
 * URLs for just that page in one query each, instead of running those queries once per user for every
 * member of the course. These tests pin the externally-visible behaviour (sort order, pagination,
 * keyword search, and per-user enrichment) so that reordering the pipeline can't silently change what
 * callers see.
 */
class CourseUserManagerTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private function subscribeStudent(EntityManagerInterface $em, User $user, Course $course): void
    {
        $subscription = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($user)
            ->setStatus(CourseRelUser::STUDENT)
            ->setRelationType(0)
        ;
        $em->persist($subscription);
        $em->flush();
    }

    public function testListSortsAndPaginatesAcrossTheFullCourseNotJustOnePage(): void
    {
        $em = $this->getEntityManager();
        $suffix = uniqid();
        $course = $this->createCourse('cum_sort_course_'.$suffix);
        $teacher = $this->createUser('cum_teacher_'.$suffix, '', '', 'ROLE_TEACHER');
        $course->addUserAsTeacher($teacher);
        $em->flush();

        // itemsPerPage is clamped to a floor of 5 by CourseUserManager::paginate(), so 7 rows
        // across 2 pages of 5 is the smallest fixture that actually exercises pagination.
        $names = ['Alice', 'Bob', 'Charlie', 'Diana', 'Elliot', 'Fiona', 'George'];
        foreach ($names as $name) {
            $student = $this->createUser('cum_'.strtolower($name).'_'.$suffix);
            $student->setFirstname($name)->setLastname($name);
            $em->persist($student);
            $this->subscribeStudent($em, $student, $course);
        }

        $token = $this->getUserTokenFromUser($teacher);

        $page1 = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/course-users/list?cid='.$course->getId().'&sort=firstname&order=asc&itemsPerPage=5&page=1',
        );
        $this->assertResponseIsSuccessful();
        $data1 = $page1->toArray();

        // totalItems must reflect every matching row, not just the ones returned on this page.
        $this->assertSame(7, $data1['totalItems']);
        $this->assertCount(5, $data1['items']);
        $this->assertSame(
            ['Alice', 'Bob', 'Charlie', 'Diana', 'Elliot'],
            array_column($data1['items'], 'firstname'),
        );

        $page2 = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/course-users/list?cid='.$course->getId().'&sort=firstname&order=asc&itemsPerPage=5&page=2',
        );
        $this->assertResponseIsSuccessful();
        $data2 = $page2->toArray();

        $this->assertSame(7, $data2['totalItems']);
        $this->assertCount(2, $data2['items']);
        $this->assertSame(['Fiona', 'George'], array_column($data2['items'], 'firstname'));
    }

    public function testSubscriptionDatesSortBeforePaginationAndKeepUnknownDatesLast(): void
    {
        $em = $this->getEntityManager();
        $suffix = uniqid();
        $course = $this->createCourse('cum_dates_course_'.$suffix);
        $teacher = $this->createUser('cum_dates_teacher_'.$suffix, '', '', 'ROLE_TEACHER');
        $course->addUserAsTeacher($teacher);
        $em->flush();

        $dates = [
            'Alice' => '2026-09-07 10:00:00',
            'Bob' => null,
            'Charlie' => '2026-09-05 10:00:00',
            'Diana' => '2026-09-02 10:00:00',
            'Elliot' => '2026-09-06 10:00:00',
            'Fiona' => '2026-09-04 10:00:00',
            'George' => '2026-09-03 10:00:00',
        ];

        foreach ($dates as $name => $date) {
            $student = $this->createUser('cum_dates_'.strtolower($name).'_'.$suffix);
            $student->setFirstname($name)->setLastname($name);
            $em->persist($student);

            $subscription = (new CourseRelUser())
                ->setCourse($course)
                ->setUser($student)
                ->setStatus(CourseRelUser::STUDENT)
                ->setRelationType(0)
                ->setSubscriptionDate(null === $date ? null : new DateTimeImmutable($date))
            ;
            $em->persist($subscription);
        }
        $em->flush();

        $token = $this->getUserTokenFromUser($teacher);
        $baseUrl = '/api/course-users/list?cid='.$course->getId().'&sort=subscriptionDate&itemsPerPage=5';

        $page1 = $this->createClientWithCredentials($token)->request('GET', $baseUrl.'&order=desc&page=1');
        $this->assertResponseIsSuccessful();
        $data1 = $page1->toArray();
        $this->assertSame(7, $data1['totalItems']);
        $this->assertSame(['Alice', 'Elliot', 'Charlie', 'Fiona', 'George'], array_column($data1['items'], 'firstname'));
        $this->assertSame('2026-09-07 10:00:00', $data1['items'][0]['subscriptionDate']);

        $page2 = $this->createClientWithCredentials($token)->request('GET', $baseUrl.'&order=desc&page=2');
        $this->assertResponseIsSuccessful();
        $data2 = $page2->toArray();
        $this->assertSame(['Diana', 'Bob'], array_column($data2['items'], 'firstname'));
        $this->assertNull($data2['items'][1]['subscriptionDate']);

        $ascending = $this->createClientWithCredentials($token)->request('GET', $baseUrl.'&order=asc&page=1');
        $this->assertResponseIsSuccessful();
        $this->assertSame(
            ['Diana', 'George', 'Fiona', 'Charlie', 'Elliot'],
            array_column($ascending->toArray()['items'], 'firstname'),
        );
    }

    public function testListKeywordSearchFiltersBeforePagination(): void
    {
        $em = $this->getEntityManager();
        $suffix = uniqid();
        $course = $this->createCourse('cum_search_course_'.$suffix);
        $teacher = $this->createUser('cum_search_teacher_'.$suffix, '', '', 'ROLE_TEACHER');
        $course->addUserAsTeacher($teacher);
        $em->flush();

        $match = $this->createUser('cum_findme_'.$suffix);
        $match->setFirstname('Findme')->setLastname('Match');
        $em->persist($match);
        $this->subscribeStudent($em, $match, $course);

        $other = $this->createUser('cum_other_'.$suffix);
        $other->setFirstname('Someone')->setLastname('Else');
        $em->persist($other);
        $this->subscribeStudent($em, $other, $course);

        $token = $this->getUserTokenFromUser($teacher);

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/course-users/list?cid='.$course->getId().'&search=findme',
        );
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['items']);
        $this->assertSame('Findme', $data['items'][0]['firstname']);
    }

    public function testListBatchesGroupsAndExtraFieldValuesPerUser(): void
    {
        $em = $this->getEntityManager();
        $suffix = uniqid();
        $course = $this->createCourse('cum_enrich_course_'.$suffix);
        $teacher = $this->createUser('cum_enrich_teacher_'.$suffix, '', '', 'ROLE_TEACHER');
        $course->addUserAsTeacher($teacher);
        $em->flush();

        $inGroup = $this->createUser('cum_ingroup_'.$suffix);
        $em->persist($inGroup);
        $this->subscribeStudent($em, $inGroup, $course);

        $noGroup = $this->createUser('cum_nogroup_'.$suffix);
        $em->persist($noGroup);
        $this->subscribeStudent($em, $noGroup, $course);

        $group = $this->createGroup('cum_group_'.$suffix, $course);
        $groupRelUser = (new CGroupRelUser())
            ->setStatus(1)
            ->setUser($inGroup)
            ->setGroup($group)
            ->setRole('member')
            ->setCId((int) $course->getId())
        ;
        $em->persist($groupRelUser);

        $extraField = (new ExtraField())
            ->setDisplayText('Cum extra '.$suffix)
            ->setVariable('cum_extra_'.$suffix)
            ->setFilter(true)
            ->setVisibleToSelf(true)
            ->setItemType(ExtraField::USER_FIELD_TYPE)
            ->setValueType(ExtraField::FIELD_TYPE_TEXT)
        ;
        $em->persist($extraField);
        $em->flush();

        $extraFieldValue = (new ExtraFieldValues())
            ->setField($extraField)
            ->setItemId($inGroup->getId())
            ->setFieldValue('some value')
        ;
        $em->persist($extraFieldValue);
        $em->flush();

        $token = $this->getUserTokenFromUser($teacher);

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/course-users/list?cid='.$course->getId().'&sort=username&order=asc&itemsPerPage=10',
        );
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $itemsByUsername = [];
        foreach ($data['items'] as $item) {
            $itemsByUsername[$item['username']] = $item;
        }

        $enrichedItem = $itemsByUsername[$inGroup->getUsername()];
        $this->assertSame(['cum_group_'.$suffix], $enrichedItem['groups']);
        $this->assertSame('some value', $enrichedItem['extraValues'][(string) $extraField->getId()]);

        $plainItem = $itemsByUsername[$noGroup->getUsername()];
        $this->assertSame([], $plainItem['groups']);
        $this->assertSame('', $plainItem['extraValues'][(string) $extraField->getId()]);
    }

    /**
     * Inside a session, CourseManager::get_user_list_from_course_code() takes a different SQL branch
     * (joins session_rel_course_rel_user instead of course_rel_user, and does not select is_tutor at
     * all), so the raw rows that sortRows()/matchesKeywordInRow() work on have a different shape.
     * This pins that the batching refactor still sorts/paginates/enriches correctly in that branch.
     */
    public function testListWorksForACourseInsideASession(): void
    {
        $em = $this->getEntityManager();
        $suffix = uniqid();
        $course = $this->createCourse('cum_session_course_'.$suffix);
        $session = $this->createSession('cum_session_'.$suffix);
        $session->addCourse($course);

        $admin = $this->getAdmin();

        foreach (['Bella', 'Amir'] as $name) {
            $student = $this->createUser('cum_sess_'.strtolower($name).'_'.$suffix);
            $student->setFirstname($name)->setLastname($name);
            $em->persist($student);
            $em->flush();
            $session->addUserInCourse(Session::STUDENT, $student, $course);
        }

        /** @var SessionRepository $sessionRepo */
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $sessionRepo->update($session);

        $token = $this->getUserTokenFromUser($admin);

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/course-users/list?cid='.$course->getId().'&sid='.$session->getId().'&sort=firstname&order=asc',
        );
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame($session->getId(), $data['sessionId']);
        $this->assertSame(2, $data['totalItems']);
        $this->assertCount(2, $data['items']);
        $this->assertSame(['Amir', 'Bella'], array_column($data['items'], 'firstname'));

        // A session course has independent dates, not those from direct course memberships.
        $subscriptions = $em->getRepository(SessionRelCourseRelUser::class)->findBy([
            'course' => $course,
            'session' => $session,
        ]);
        $this->assertCount(2, $subscriptions);
        foreach ($subscriptions as $subscription) {
            $subscribedUser = $subscription->getUser();
            $this->assertInstanceOf(User::class, $subscribedUser);
            $subscription->setSubscriptionDate(new DateTimeImmutable(
                'Amir' === $subscribedUser->getFirstname()
                    ? '2026-08-01 08:00:00'
                    : '2026-08-02 08:00:00',
            ));
        }
        $em->flush();

        $byDate = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/course-users/list?cid='.$course->getId().'&sid='.$session->getId().'&sort=subscriptionDate&order=desc',
        );
        $this->assertResponseIsSuccessful();
        $this->assertSame(['Bella', 'Amir'], array_column($byDate->toArray()['items'], 'firstname'));
    }
}
