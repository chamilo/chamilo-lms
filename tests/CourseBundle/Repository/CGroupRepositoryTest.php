<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupCategory;
use Chamilo\CourseBundle\Entity\CGroupRelTutor;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Repository\CGroupCategoryRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CGroupRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CGroupRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $group = (new CGroup())
            ->setName('Group')
            ->setParent($course)
            ->setCreator($teacher)
            ->setStatus(true)
            ->setDescription('desc')
            ->setAnnouncementsState(0)
            ->setChatState(0)
            ->setDocState(0)
            ->setDocumentAccess(0)
            ->setCalendarState(0)
            ->setWikiState(0)
            ->setWorkState(0)
            ->setSelfRegistrationAllowed(true)
            ->setSelfUnregistrationAllowed(true)
            ->setMaxStudent(100)
        ;

        $this->assertHasNoEntityViolations($group);
        $em->persist($group);
        $em->flush();

        $this->assertTrue($group->getSelfRegistrationAllowed());
        $this->assertTrue($group->getSelfUnregistrationAllowed());

        $this->assertFalse($group->hasTutors());
        $this->assertFalse($group->hasMembers());
        $this->assertTrue($group->getStatus());
        $this->assertSame('desc', $group->getDescription());
        $this->assertSame(100, $group->getMaxStudent());
        $this->assertSame(0, $group->getAnnouncementsState());
        $this->assertSame(0, $group->getChatState());
        $this->assertSame(0, $group->getDocState());
        $this->assertSame(0, $group->getDocumentAccess());
        $this->assertSame(0, $group->getCalendarState());
        $this->assertSame(0, $group->getWikiState());
        $this->assertSame(0, $group->getWorkState());
        $this->assertSame($group->getResourceIdentifier(), $group->getIid());

        $this->assertSame(1, $repo->count([]));

        $this->assertNotNull($repo->findOneByTitle('Group'));

        $repo->delete($group);
        $this->assertSame(0, $repo->count([]));
    }

    public function testCreateWithCategory(): void
    {
        $em = $this->getEntityManager();

        $groupRepo = self::getContainer()->get(CGroupRepository::class);
        $categoryRepo = self::getContainer()->get(CGroupCategoryRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $category = (new CGroupCategory())
            ->setTitle('category')
            ->setParent($course)
            ->setCreator($teacher)
            ->setMaxStudent(100)
        ;
        $em->persist($category);
        $em->flush();

        $group = (new CGroup())
            ->setName('Group')
            ->setCategory($category)
            ->setParent($course)
            ->setCreator($teacher)
            ->setMaxStudent(100)
        ;
        $em->persist($group);
        $em->flush();

        $this->assertSame(1, $groupRepo->count([]));
        $this->assertNotNull($groupRepo->findOneByTitle('Group'));

        $groupRepo->delete($group);

        $this->assertSame(0, $groupRepo->count([]));
        $this->assertSame(1, $categoryRepo->count([]));
    }

    public function testCreateAddUsers(): void
    {
        $em = $this->getEntityManager();
        $groupRepo = self::getContainer()->get(CGroupRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');
        $student = $this->createUser('student');
        $tutor = $this->createUser('tutor');
        $otherUser = $this->createUser('other');

        $courseId = $course->getId();

        $group = (new CGroup())
            ->setName('Group')
            ->setParent($course)
            ->setCreator($teacher)
            ->setMaxStudent(100)
        ;
        $em->persist($group);
        $em->flush();

        $this->assertFalse($group->hasTutors());
        $this->assertFalse($group->hasMembers());

        $groupRelUser = (new CGroupRelUser())
            ->setStatus(1)
            ->setUser($student)
            ->setGroup($group)
            ->setRole('test')
            ->setCId($courseId)
        ;
        $em->persist($groupRelUser);
        $em->flush();

        $groupRelTutor = (new CGroupRelTutor())
            ->setGroup($group)
            ->setUser($tutor)
            ->setCId($courseId)
        ;
        $em->persist($groupRelTutor);
        $em->flush();
        $em->clear();

        /** @var CGroup $group */
        $group = $groupRepo->find($group->getIid());

        $this->assertSame($groupRelTutor->getCId(), $courseId);

        $this->assertSame($group->getResourceIdentifier(), $group->getIid());
        $this->assertSame(1, $group->getMembers()->count());
        $this->assertSame(1, $group->getTutors()->count());

        $this->assertTrue($group->hasTutors());
        $this->assertTrue($group->hasMembers());
        $this->assertTrue($group->hasTutor($tutor));
        $this->assertTrue($group->hasMember($student));
        $this->assertFalse($group->hasTutor($otherUser));
        $this->assertFalse($group->hasMember($otherUser));

        $this->assertSame(1, $groupRepo->count([]));
        $this->assertNotNull($groupRepo->findOneByTitle('Group'));

        $course = $this->getCourse($course->getId());
        $courseRepo->delete($course);

        $this->assertSame(0, $groupRepo->count([]));
        $this->assertSame(0, $courseRepo->count([]));
    }

    public function testFindAllByCourse(): void
    {
        $repo = self::getContainer()->get(CGroupRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $group = (new CGroup())
            ->setName('Group')
            ->setParent($course)
            ->setCreator($teacher)
            ->setMaxStudent(100)
            ->addCourseLink($course)
        ;
        $repo->create($group);

        $qb = $repo->findAllByCourse($course);
        $this->assertCount(1, $qb->getQuery()->getResult());
    }
}
