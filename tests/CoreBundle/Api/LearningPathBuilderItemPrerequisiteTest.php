<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The learning path builder and the MCP tool share LearningPathItemPrerequisiteService: the endpoint
 * must keep answering 204 / 400 / 404 exactly as it did before the rules moved there.
 */
final class LearningPathBuilderItemPrerequisiteTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTeacherSetsThePrerequisiteOfALaterItem(): void
    {
        [$course, $teacher, $lp, $first, $second] = $this->bootstrap('ok');

        $this->patch($teacher, $course, $second, ['lpId' => $lp->getIid(), 'prerequisiteId' => $first->getIid()]);

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame((string) $first->getIid(), $this->freshItem($second)->getPrerequisite());
    }

    public function testAnItemCannotRequireALaterItem(): void
    {
        [$course, $teacher, $lp, $first, $second] = $this->bootstrap('order');

        $this->patch($teacher, $course, $first, ['lpId' => $lp->getIid(), 'prerequisiteId' => $second->getIid()]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertNotSame((string) $second->getIid(), (string) $this->freshItem($first)->getPrerequisite());
    }

    public function testAPrerequisiteOfAnotherLearningPathIsNotFound(): void
    {
        [$course, $teacher, $lp, , $second] = $this->bootstrap('foreign');
        [, , , $foreign] = $this->bootstrap('foreign_lp', $course, $teacher);

        $this->patch($teacher, $course, $second, ['lpId' => $lp->getIid(), 'prerequisiteId' => $foreign->getIid()]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testZeroClearsThePrerequisite(): void
    {
        [$course, $teacher, $lp, $first, $second] = $this->bootstrap('clear');
        $this->patch($teacher, $course, $second, ['lpId' => $lp->getIid(), 'prerequisiteId' => $first->getIid()]);

        $this->patch($teacher, $course, $second, ['lpId' => $lp->getIid(), 'prerequisiteId' => 0]);

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame('', (string) $this->freshItem($second)->getPrerequisite());
    }

    /**
     * @return array{0: Course, 1: User, 2: CLp, 3: CLpItem, 4: CLpItem}
     */
    private function bootstrap(string $suffix, ?Course $course = null, ?User $teacher = null): array
    {
        if (null === $course) {
            /** @var CourseRepository $courseRepo */
            $courseRepo = self::getContainer()->get(CourseRepository::class);
            $course = $this->createCourse('LPB Prereq '.$suffix);
            $teacher = $this->createUser('lpb_prereq_teacher_'.$suffix);
            $course->addUserAsTeacher($teacher);
            $courseRepo->update($course);
        }

        /** @var CLpRepository $lpRepo */
        $lpRepo = self::getContainer()->get(CLpRepository::class);

        /** @var CLpItemRepository $itemRepo */
        $itemRepo = self::getContainer()->get(CLpItemRepository::class);

        $lp = (new CLp())
            ->setTitle('LP '.$suffix)
            ->setParent($course)
            ->setCreator($teacher)
            ->setLpType(CLp::LP_TYPE)
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;
        $lpRepo->createLp($lp);
        $root = $itemRepo->getRootItem((int) $lp->getIid());

        $items = [];
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
            $items[] = $item;
        }

        return [$course, $teacher, $lp, $items[0], $items[1]];
    }

    /**
     * @param array<string, mixed> $json
     */
    private function patch(User $user, Course $course, CLpItem $item, array $json): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'PATCH',
            '/api/learning_path_builder_items/'.$item->getIid().'/prerequisite?cid='.$course->getId(),
            [
                'json' => $json,
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
            ]
        );
    }

    private function freshItem(CLpItem $item): CLpItem
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->getRepository(CLpItem::class)->find($item->getIid());
    }
}
