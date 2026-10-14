<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * A teacher of one course must not touch the links and link categories of another course.
 *
 * The contextual teacher roles answer for the course named in `cid`, so every case here passes
 * the teacher's own `cid` while pointing at an id (a link, a category, a parent resource node)
 * that belongs to a different course. Each denied case has its positive twin
 * (testTeacherCanManageLinksOfOwnCourse), which proves the 403 does not come from a teacher the
 * test failed to set up.
 */
final class CLinkCourseScopeApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * @return array{
     *     teacher: User,
     *     course: Course,
     *     foreignTeacher: User,
     *     foreignCourse: Course,
     *     foreignLinkId: int,
     *     foreignCategoryId: int
     * }
     */
    private function bootstrapScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('CLSA'.$suffix);
        $foreignCourse = $this->createCourse('CLSB'.$suffix);
        $teacher = $this->createUser('cls_teacher_'.$suffix);
        $foreignTeacher = $this->createUser('cls_fteacher_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $courseRepo->update($course);
        $foreignCourse->addUserAsTeacher($foreignTeacher);
        $courseRepo->update($foreignCourse);

        $foreignCategoryId = $this->createCategory($foreignTeacher, $foreignCourse, 'foreign category');
        $foreignLinkId = $this->createLink($foreignTeacher, $foreignCourse, 'foreign link');

        return [
            'teacher' => $teacher,
            'course' => $course,
            'foreignTeacher' => $foreignTeacher,
            'foreignCourse' => $foreignCourse,
            'foreignLinkId' => $foreignLinkId,
            'foreignCategoryId' => $foreignCategoryId,
        ];
    }

    private function nodeId(Course $course): int
    {
        return (int) $course->getResourceNode()?->getId();
    }

    /**
     * @return array<string, mixed>
     */
    private function linkPayload(int $parentNodeId, string $title, int $categoryId = 0): array
    {
        return [
            'url' => 'https://example.com/'.$title,
            'title' => $title,
            'description' => '',
            'category' => $categoryId,
            'target' => '_blank',
            'parentResourceNodeId' => $parentNodeId,
            'resourceLinkList' => [['visibility' => 2]],
        ];
    }

    private function createLink(User $teacher, Course $course, string $title, int $categoryId = 0): int
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($teacher))->request(
            'POST',
            '/api/links?cid='.$course->getId().'&sid=0&gid=0',
            ['json' => $this->linkPayload($this->nodeId($course), $title, $categoryId)],
        );
        $this->assertResponseStatusCodeSame(201);

        return (int) $response->toArray()['iid'];
    }

    private function createCategory(User $teacher, Course $course, string $title): int
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($teacher))->request(
            'POST',
            '/api/link_categories?cid='.$course->getId().'&sid=0&gid=0',
            ['json' => [
                'category_title' => $title,
                'description' => '',
                'parentResourceNodeId' => $this->nodeId($course),
                'resourceLinkList' => json_encode([['visibility' => 2, 'cid' => (int) $course->getId()]]),
            ]],
        );
        $this->assertResponseStatusCodeSame(201);

        return (int) $response->toArray()['iid'];
    }

    public function testTeacherCanManageLinksOfOwnCourse(): void
    {
        $ctx = $this->bootstrapScenario('own');

        $categoryId = $this->createCategory($ctx['teacher'], $ctx['course'], 'own category');
        $linkId = $this->createLink($ctx['teacher'], $ctx['course'], 'own link', $categoryId);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'PUT',
            '/api/links/'.$linkId.'?cid='.$ctx['course']->getId().'&sid=0&gid=0',
            ['json' => [
                'url' => 'https://example.com/edited',
                'title' => 'edited',
                'description' => '',
                'category' => $categoryId,
                'target' => '_blank',
                'resourceLinkList' => [],
            ]],
        );
        $this->assertResponseStatusCodeSame(200);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'DELETE',
            '/api/links/'.$linkId.'?cid='.$ctx['course']->getId().'&sid=0&gid=0',
        );
        $this->assertResponseStatusCodeSame(204);
    }

    public function testTeacherCannotDeleteLinkOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('del');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'DELETE',
            '/api/links/'.$ctx['foreignLinkId'].'?cid='.$ctx['course']->getId().'&sid=0&gid=0',
        );
        $this->assertResponseStatusCodeSame(403);

        // The link is still there for its own teacher.
        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['foreignTeacher']))->request(
            'GET',
            '/api/links/'.$ctx['foreignLinkId'].'?cid='.$ctx['foreignCourse']->getId().'&sid=0&gid=0',
        );
        $this->assertResponseStatusCodeSame(200);
    }

    public function testTeacherCannotRewriteLinkOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('put');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'PUT',
            '/api/links/'.$ctx['foreignLinkId'].'?cid='.$ctx['course']->getId().'&sid=0&gid=0',
            ['json' => [
                'url' => 'https://evil.example',
                'title' => 'rewritten',
                'description' => '',
                'category' => 0,
                'target' => '_self',
                'resourceLinkList' => [],
            ]],
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTeacherCannotDeleteCategoryOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('dcat');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'DELETE',
            '/api/link_categories/'.$ctx['foreignCategoryId'].'?cid='.$ctx['course']->getId().'&sid=0&gid=0',
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTeacherCannotNestLinkUnderNodeOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('nlk');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'POST',
            '/api/links?cid='.$ctx['course']->getId().'&sid=0&gid=0',
            ['json' => $this->linkPayload($this->nodeId($ctx['foreignCourse']), 'nested')],
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTeacherCannotNestCategoryUnderNodeOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('ncat');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'POST',
            '/api/link_categories?cid='.$ctx['course']->getId().'&sid=0&gid=0',
            ['json' => [
                'category_title' => 'nested',
                'description' => '',
                'parentResourceNodeId' => $this->nodeId($ctx['foreignCourse']),
                'resourceLinkList' => json_encode([['visibility' => 2, 'cid' => (int) $ctx['course']->getId()]]),
            ]],
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTeacherCannotMoveCategoryUnderNodeOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('mcat');
        $categoryId = $this->createCategory($ctx['teacher'], $ctx['course'], 'own category');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'PUT',
            '/api/link_categories/'.$categoryId.'?cid='.$ctx['course']->getId().'&sid=0&gid=0',
            ['json' => [
                'category_title' => 'moved',
                'description' => '',
                'parentResourceNodeId' => $this->nodeId($ctx['foreignCourse']),
                'resourceLinkList' => '[]',
            ]],
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTeacherCannotCreateLinkInCategoryOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('ccat');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'POST',
            '/api/links?cid='.$ctx['course']->getId().'&sid=0&gid=0',
            ['json' => $this->linkPayload($this->nodeId($ctx['course']), 'categorized', $ctx['foreignCategoryId'])],
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTeacherCannotMoveOwnLinkToCategoryOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('lcat');
        $linkId = $this->createLink($ctx['teacher'], $ctx['course'], 'own link');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'PUT',
            '/api/links/'.$linkId.'?cid='.$ctx['course']->getId().'&sid=0&gid=0',
            ['json' => [
                'url' => 'https://example.com/own',
                'title' => 'own link',
                'description' => '',
                'category' => $ctx['foreignCategoryId'],
                'target' => '_blank',
                'resourceLinkList' => [],
            ]],
        );
        $this->assertResponseStatusCodeSame(403);
    }
}
