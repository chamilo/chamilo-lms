<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Component\CourseCopy\Course as LegacyCourse;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use ReflectionProperty;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * A backup lists the items of a learning path by level (CourseBuilder) or by display_order (the
 * Chamilo sidecar of a Moodle export), never depth-first. Rebuilding the tree from that list order
 * hung items under the wrong chapter and shuffled the learning path: its structure must come from
 * parent_item_id.
 */
final class CourseRestorerLearningPathTreeTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testItemsKeepTheirChapterAndOrderWhenNotListedDepthFirst(): void
    {
        self::bootKernel();
        Container::setContainer(self::getContainer());
        Database::setManager($this->getEntityManager());
        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::getContainer()->get('request_stack')->push($request);
        $admin = $this->getAdmin();
        self::getContainer()->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($admin, 'main', $admin->getRoles())
        );

        $course = $this->createCourse('Learning path tree restore');
        $this->assertNotNull($course);

        // Source tree: Chapter A (A1, A2), then Chapter B (B1). The display_order values only order
        // siblings: they are global, so the children of A come before A itself in the list.
        $item = static fn (int $id, int $parentId, int $level, int $order, string $title): array => [
            'id' => $id,
            'parent_item_id' => $parentId,
            'level' => $level,
            'display_order' => $order,
            'item_type' => 'dir',
            'title' => $title,
            'path' => '',
        ];
        $learningPath = new stdClass();
        $learningPath->title = 'Tree';
        $learningPath->linked_resources = [];
        $learningPath->items = [
            $item(20, 10, 2, 1, 'A1'),
            $item(22, 11, 2, 2, 'B1'),
            $item(10, 1, 1, 3, 'Chapter A'),
            $item(21, 10, 2, 4, 'A2'),
            $item(11, 1, 1, 5, 'Chapter B'),
        ];

        $backup = new LegacyCourse();
        $backup->code = $course->getCode();
        $backup->resources = [RESOURCE_LEARNPATH => [187 => $learningPath]];

        $restorer = new CourseRestorer($backup);
        (new ReflectionProperty(CourseRestorer::class, 'destination_course_id'))->setValue($restorer, $course->getId());
        $restorer->restore_learnpaths(0, false, $course->getCode());

        $lp = $this->getEntityManager()->getRepository(CLp::class)->findOneBy(['title' => 'Tree']);
        $this->assertNotNull($lp);

        /** @var CLpItem[] $items */
        $items = $this->getEntityManager()->getRepository(CLpItem::class)->findBy(['lp' => $lp], ['displayOrder' => 'ASC']);
        $tree = [];
        foreach ($items as $restored) {
            if ('root' === $restored->getItemType()) {
                continue;
            }
            $parent = $restored->getParent();
            $tree[] = $restored->getTitle().' < '.('root' === $parent?->getItemType() ? 'root' : $parent?->getTitle());
        }

        $this->assertSame(
            ['Chapter A < root', 'A1 < Chapter A', 'A2 < Chapter A', 'Chapter B < root', 'B1 < Chapter B'],
            $tree
        );
    }
}
