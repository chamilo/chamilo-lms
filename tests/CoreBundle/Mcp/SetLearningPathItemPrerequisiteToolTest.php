<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Mcp\ManageCourseLearningPathTool;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Mcp\Exception\ToolCallException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class SetLearningPathItemPrerequisiteToolTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testPrerequisiteIsPersistedAndReadBackInTheStructure(): void
    {
        self::bootKernel();
        $course = $this->createCourse('LPP set '.uniqid());
        $this->authenticateAsTeacherOf($course);
        [$lpId, $first, $second] = $this->createLearningPathWithItems($course, 2);

        $result = $this->tool()->setLearningPathItemPrerequisite((int) $course->getId(), $lpId, $second, $first);

        self::assertTrue($result['updated']);
        $item = $this->findItem($result['items'], $second);
        self::assertSame($first, $item['prerequisite_item_id'], 'The prerequisite must be read back from the persisted structure.');
        self::assertSame(0.0, $item['prerequisite_min_score']);
        self::assertSame(100.0, $item['prerequisite_max_score']);
        self::assertNull($this->findItem($result['items'], $first)['prerequisite_item_id']);
    }

    public function testZeroClearsThePrerequisite(): void
    {
        self::bootKernel();
        $course = $this->createCourse('LPP clear '.uniqid());
        $this->authenticateAsTeacherOf($course);
        [$lpId, $first, $second] = $this->createLearningPathWithItems($course, 2);
        $courseId = (int) $course->getId();

        $this->tool()->setLearningPathItemPrerequisite($courseId, $lpId, $second, $first);
        $result = $this->tool()->setLearningPathItemPrerequisite($courseId, $lpId, $second, 0);

        self::assertNull($this->findItem($result['items'], $second)['prerequisite_item_id']);
    }

    public function testAnItemCannotRequireALaterItem(): void
    {
        self::bootKernel();
        $course = $this->createCourse('LPP order '.uniqid());
        $this->authenticateAsTeacherOf($course);
        [$lpId, $first, $second] = $this->createLearningPathWithItems($course, 2);

        $this->expectException(ToolCallException::class);
        $this->tool()->setLearningPathItemPrerequisite((int) $course->getId(), $lpId, $first, $second);
    }

    public function testAnItemCannotRequireItself(): void
    {
        self::bootKernel();
        $course = $this->createCourse('LPP self '.uniqid());
        $this->authenticateAsTeacherOf($course);
        [$lpId, $first] = $this->createLearningPathWithItems($course, 2);

        $this->expectException(ToolCallException::class);
        $this->tool()->setLearningPathItemPrerequisite((int) $course->getId(), $lpId, $first, $first);
    }

    public function testAPrerequisiteOfAnotherLearningPathIsRefused(): void
    {
        self::bootKernel();
        $course = $this->createCourse('LPP foreign '.uniqid());
        $this->authenticateAsTeacherOf($course);
        [$lpId, , $second] = $this->createLearningPathWithItems($course, 2);
        [, $foreignItem] = $this->createLearningPathWithItems($course, 1);

        $this->expectException(ToolCallException::class);
        $this->tool()->setLearningPathItemPrerequisite((int) $course->getId(), $lpId, $second, $foreignItem);
    }

    public function testANegativePrerequisiteIdIsRefusedInsteadOfClearingTheExistingOne(): void
    {
        self::bootKernel();
        $course = $this->createCourse('LPP negative '.uniqid());
        $this->authenticateAsTeacherOf($course);
        [$lpId, $first, $second] = $this->createLearningPathWithItems($course, 2);
        $courseId = (int) $course->getId();
        $this->tool()->setLearningPathItemPrerequisite($courseId, $lpId, $second, $first);

        try {
            $this->tool()->setLearningPathItemPrerequisite($courseId, $lpId, $second, -5);
            self::fail('A negative prerequisite id must be refused.');
        } catch (ToolCallException) {
            // Expected.
        }

        $items = $this->tool()->getLearningPathStructure($courseId, $lpId)['items'];
        self::assertSame($first, $this->findItem($items, $second)['prerequisite_item_id'], 'The existing prerequisite must survive a refused call.');
    }

    public function testAPrerequisiteThatDoesNotExistIsRefused(): void
    {
        self::bootKernel();
        $course = $this->createCourse('LPP missing '.uniqid());
        $this->authenticateAsTeacherOf($course);
        [$lpId, , $second] = $this->createLearningPathWithItems($course, 2);

        $this->expectException(ToolCallException::class);
        $this->tool()->setLearningPathItemPrerequisite((int) $course->getId(), $lpId, $second, 2_000_000_000);
    }

    public function testATeacherOfAnotherCourseIsDenied(): void
    {
        self::bootKernel();
        $course = $this->createCourse('LPP owner '.uniqid());
        $otherCourse = $this->createCourse('LPP other '.uniqid());
        $this->authenticateAsTeacherOf($course);
        [$lpId, $first, $second] = $this->createLearningPathWithItems($course, 2);
        $this->authenticateAsTeacherOf($otherCourse);

        $this->expectException(ToolCallException::class);
        $this->tool()->setLearningPathItemPrerequisite((int) $course->getId(), $lpId, $second, $first);
    }

    public function testALearningPathAttachedToASessionIsReachedOnlyThroughThatSession(): void
    {
        self::bootKernel();
        $course = $this->createCourse('LPP session '.uniqid());
        $session = $this->linkedSession($course);
        $this->authenticateAsTeacherOf($course);
        $courseId = (int) $course->getId();
        $sessionId = (int) $session->getId();
        [$lpId, $first, $second] = $this->createSessionOnlyLearningPath($course, $session);

        try {
            $this->tool()->setLearningPathItemPrerequisite($courseId, $lpId, $second, $first);
            self::fail('A learning path attached to a session must not be editable as a base-course one.');
        } catch (ToolCallException) {
            // Expected: without sessionId only the base-course learning paths are looked up.
        }

        $result = $this->tool()->setLearningPathItemPrerequisite($courseId, $lpId, $second, $first, sessionId: $sessionId);

        self::assertSame($first, $this->findItem($result['items'], $second)['prerequisite_item_id']);
        self::assertSame($lpId, $result['learning_path']['learning_path_id']);
    }

    public function testABaseCourseLearningPathCanBeEditedFromASessionOfTheCourse(): void
    {
        self::bootKernel();
        $course = $this->createCourse('LPP base in session '.uniqid());
        $session = $this->linkedSession($course);
        $this->authenticateAsTeacherOf($course);
        [$lpId, $first, $second] = $this->createLearningPathWithItems($course, 2);

        $result = $this->tool()->setLearningPathItemPrerequisite((int) $course->getId(), $lpId, $second, $first, sessionId: (int) $session->getId());

        self::assertSame($first, $this->findItem($result['items'], $second)['prerequisite_item_id']);
    }

    public function testALearningPathOfAnotherSessionIsNotReachedThroughThisOne(): void
    {
        self::bootKernel();
        $course = $this->createCourse('LPP two sessions '.uniqid());
        $sessionA = $this->linkedSession($course);
        $sessionB = $this->linkedSession($course);
        $this->authenticateAsTeacherOf($course);
        [$lpId, $first, $second] = $this->createSessionOnlyLearningPath($course, $sessionA);

        $this->expectException(ToolCallException::class);
        $this->tool()->setLearningPathItemPrerequisite((int) $course->getId(), $lpId, $second, $first, sessionId: (int) $sessionB->getId());
    }

    public function testASessionNotLinkedToTheCourseIsRefused(): void
    {
        self::bootKernel();
        $course = $this->createCourse('LPP unlinked '.uniqid());
        $unlinked = $this->createSession('LPP unlinked session '.uniqid());
        $this->authenticateAsTeacherOf($course);
        [$lpId, $first, $second] = $this->createLearningPathWithItems($course, 2);

        $this->expectException(ToolCallException::class);
        $this->tool()->setLearningPathItemPrerequisite((int) $course->getId(), $lpId, $second, $first, sessionId: (int) $unlinked->getId());
    }

    private function linkedSession(Course $course): Session
    {
        $session = $this->createSession('LPP linked session '.uniqid());
        $em = $this->getEntityManager();
        $course->setVisibility(Course::REGISTERED);
        $session->addCourse($course);
        $em->persist($course);
        $em->persist($session);
        $em->flush();

        return $session;
    }

    /**
     * A learning path whose only resource link is the one of the session.
     *
     * @return list<int> the learning path id followed by the ids of its two items
     */
    private function createSessionOnlyLearningPath(Course $course, Session $session): array
    {
        /** @var CLpRepository $lpRepo */
        $lpRepo = self::getContainer()->get(CLpRepository::class);

        /** @var CLpItemRepository $itemRepo */
        $itemRepo = self::getContainer()->get(CLpItemRepository::class);

        $lp = (new CLp())
            ->setTitle('LPP session only '.uniqid())
            ->setParent($course)
            ->setLpType(CLp::LP_TYPE)
            ->addCourseLink($course, $session)
        ;
        $lpRepo->createLp($lp);
        $root = $itemRepo->getRootItem((int) $lp->getIid());

        $ids = [(int) $lp->getIid()];
        foreach ([1, 2] as $order) {
            $item = (new CLpItem())
                ->setLp($lp)
                ->setParent($root)
                ->setItemType('document')
                ->setTitle('Item '.$order)
                ->setPath('0')
                ->setRef('0')
                ->setDisplayOrder($order)
                ->setPrerequisite('')
            ;
            $itemRepo->create($item);
            $ids[] = (int) $item->getIid();
        }

        return $ids;
    }

    /**
     * @return list<int> the learning path id followed by the ids of the items, in display order
     */
    private function createLearningPathWithItems(Course $course, int $count): array
    {
        $created = $this->tool()->createEmptyLearningPath((int) $course->getId(), 'LPP '.uniqid());
        $lpId = (int) $created['learning_path']['learning_path_id'];

        /** @var CLpRepository $lpRepo */
        $lpRepo = self::getContainer()->get(CLpRepository::class);

        /** @var CLpItemRepository $itemRepo */
        $itemRepo = self::getContainer()->get(CLpItemRepository::class);
        $lp = $lpRepo->find($lpId);
        $root = $itemRepo->getRootItem($lpId);

        $ids = [$lpId];
        for ($i = 1; $i <= $count; ++$i) {
            $item = (new CLpItem())
                ->setLp($lp)
                ->setParent($root)
                ->setItemType('document')
                ->setTitle('Item '.$i)
                ->setPath('0')
                ->setRef('0')
                ->setDisplayOrder($i)
                ->setPrerequisite('')
            ;
            $itemRepo->create($item);
            $ids[] = (int) $item->getIid();
        }

        return $ids;
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array<string, mixed>
     */
    private function findItem(array $items, int $itemId): array
    {
        foreach ($items as $item) {
            if ($itemId === $item['item_id']) {
                return $item;
            }
        }

        self::fail('Item '.$itemId.' is missing from the returned structure.');
    }

    private function tool(): ManageCourseLearningPathTool
    {
        return self::getContainer()->get(ManageCourseLearningPathTool::class);
    }

    private function authenticateAsTeacherOf(Course $course): void
    {
        $teacher = $this->createUser('lpp_teacher_'.uniqid('', true), '', '', 'ROLE_TEACHER');

        $em = $this->getEntityManager();
        $em->persist(
            (new CourseRelUser())
                ->setCourse($course)
                ->setUser($teacher)
                ->setStatus(CourseRelUser::TEACHER)
                ->setRelationType(0)
        );
        $em->flush();
        // A real request loads the course and its teachers fresh from the database.
        $em->refresh($course);

        self::getContainer()->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($teacher, 'api', $teacher->getRoles())
        );
    }
}
