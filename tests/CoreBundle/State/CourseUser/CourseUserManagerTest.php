<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\State\CourseUser;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
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
    }
}
