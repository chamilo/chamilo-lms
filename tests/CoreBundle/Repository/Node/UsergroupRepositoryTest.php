<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelCourse;
use Chamilo\CoreBundle\Entity\UsergroupRelQuestion;
use Chamilo\CoreBundle\Entity\UsergroupRelSession;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Chamilo\CoreBundle\Entity\UserGroupRelUserGroup;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UsergroupRepositoryTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();
        $repo = self::getContainer()->get(UsergroupRepository::class);

        $group = (new Usergroup())
            ->setName('test')
            ->setDescription('desc')
            ->setGroupType(1)
            ->setUrl('url')
            ->setAuthorId('')
            ->setAllowMembersToLeaveGroup(1)
            ->setVisibility(GROUP_PERMISSION_OPEN)
            ->addAccessUrl($this->getAccessUrl())
            ->setCreator($this->getUser('admin'))
        ;

        $this->assertHasNoEntityViolations($group);
        $repo->create($group);

        $this->assertSame('test', (string) $group);

        $this->assertSame('/img/icons/64/group_na.png', $group->getDefaultIllustration(64));
        $this->assertSame(1, $repo->count([]));

        $group->setName('test2');
        $repo->update($group);

        $this->assertSame(1, $repo->count([]));

        $repo->delete($group);
        $this->assertSame(0, $repo->count([]));
    }

    public function testCreateWithAssociations(): void
    {
        self::bootKernel();
        $repo = self::getContainer()->get(UsergroupRepository::class);
        $em = $this->getEntityManager();

        $group = (new Usergroup())
            ->setName('test')
            ->addAccessUrl($this->getAccessUrl())
            ->setCreator($this->getUser('admin'))
        ;
        $repo->create($group);

        $this->assertSame(1, $repo->count([]));

        $course = $this->createCourse('new');

        $userGroupRelCourse = (new UsergroupRelCourse())
            ->setCourse($course)
            ->setUsergroup($group)
        ;
        $em->persist($userGroupRelCourse);

        $teacher = $this->createUser('teacher');

        $question = (new CQuizQuestion())
            ->setQuestionCode('code')
            ->setQuestion('question')
            ->setType(1)
            ->setLevel(1)
            ->setPonderation(100)
            ->setPosition(1)
            ->setParent($course)
            ->addCourseLink($course)
            ->setCreator($teacher)
        ;
        $em->persist($question);

        $userGroupRelQuestion = (new UsergroupRelQuestion())
            ->setQuestion($question)
            ->setUsergroup($group)
        ;
        $em->persist($userGroupRelQuestion);

        $session = $this->createSession('session');

        $userGroupRelSession = (new UsergroupRelSession())
            ->setSession($session)
            ->setUsergroup($group)
        ;
        $em->persist($userGroupRelSession);

        $testUser = $this->createUser('test');
        $userGroupRelUser = (new UsergroupRelUser())
            ->setUser($testUser)
            ->setUsergroup($group)
            ->setRelationType(1)
        ;
        $em->persist($userGroupRelUser);

        //UserGroupRelUserGroup.php

        $group->getCourses()->add($userGroupRelCourse);
        $group->getQuestions()->add($userGroupRelQuestion);
        $group->getSessions()->add($userGroupRelSession);
        $group->getUsers()->add($userGroupRelUser);

        $em->persist($group);
        $em->flush();

        $this->assertNotNull($userGroupRelCourse->getId());
        $this->assertSame(1, $group->getCourses()->count());
        $this->assertSame(1, $group->getQuestions()->count());
        $this->assertSame(1, $group->getSessions()->count());
        $this->assertSame(1, $group->getUsers()->count());

        $repo->delete($group);

        $this->assertNotNull($this->getUser('teacher'));
        $this->assertNotNull($this->getCourse($course->getId()));
        $this->assertSame(0, $repo->count([]));
    }
}
