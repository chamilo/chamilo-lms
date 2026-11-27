<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

final class CourseDescriptionOrderApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTeacherReordersWithoutChangingContentAndLearnerCannotReorder(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $course = $this->createCourse('Description order '.$suffix);
        $foreign = $this->createCourse('Foreign description '.$suffix);
        $teacher = $this->createUser('cd_order_teacher_'.$suffix);
        $learner = $this->createUser('cd_order_learner_'.$suffix);
        $course->addUserAsTeacher($teacher);
        $course->addSubscriptionForUser($learner, 0, null, CourseRelUser::STUDENT);
        $foreign->addUserAsTeacher($teacher);
        $repository = self::getContainer()->get(CourseRepository::class);
        $repository->update($course);
        $repository->update($foreign);
        $cid = (int) $course->getId();
        $foreignId = (int) $foreign->getId();
        $teacherToken = $this->getUserTokenFromUser($teacher);
        $learnerToken = $this->getUserTokenFromUser($learner);
        $client = $this->createClientWithCredentials($teacherToken);

        $a = $this->createDescription($client, $cid, 'A', 17);
        $b = $this->createDescription($client, $cid, 'B', 17);
        $c = $this->createDescription($client, $cid, 'C', 32);
        $other = $this->createDescription($client, $foreignId, 'Foreign');
        $this->assertOrder($client, $cid, [$a, $b, $c]);
        $before = $this->linkPositions($cid);
        $foreignBefore = $this->linkPositions($foreignId);

        $this->move($client, $cid, $c, 'up');
        $this->move($client, $cid, $c, 'up');
        $items = $this->assertOrder($client, $cid, [$c, $a, $b]);
        self::assertSame([32, 17, 17], array_column($items, 'progress'));
        $after = $this->linkPositions($cid);
        self::assertSame($before[$a], $after[$c]);
        self::assertSame($before[$b], $after[$a]);
        self::assertSame($before[$c], $after[$b]);
        self::assertSame($foreignBefore, $this->linkPositions($foreignId));
        $this->move($client, $cid, $c, 'down');
        $this->assertOrder($client, $cid, [$a, $c, $b]);
        $this->move($client, $cid, $c, 'up');
        $this->assertOrder($client, $cid, [$c, $a, $b]);
        self::assertStringContainsString('Content C', $items[0]['content']);
        self::assertTrue($items[0]['canReorder']);
        $catalogueCourse = self::getContainer()->get(CourseRepository::class)->find($cid);
        $catalogueItems = self::getContainer()->get(CCourseDescriptionRepository::class)->findAllInCourseForCatalogue($catalogueCourse);
        self::assertSame([$c, $a, $b], array_map(static fn ($item): int => $item->getIid(), $catalogueItems));

        // Moving past the boundary is a no-op; new items append to the ordered list.
        $this->move($client, $cid, $c, 'up');
        $d = $this->createDescription($client, $cid, 'D');
        $this->assertOrder($client, $cid, [$c, $a, $b, $d]);
        $client->request('PATCH', '/api/course-description/'.$a.'?cid='.$cid, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['descriptionType' => 8, 'title' => 'Edited A', 'content' => '<p>Edited content</p>', 'progress' => 17, 'language' => '', 'enableSearch' => false],
        ]);
        self::assertResponseIsSuccessful();
        $items = $this->assertOrder($client, $cid, [$c, $a, $b, $d]);
        self::assertSame('Edited A', $items[1]['title']);
        $client->request('DELETE', '/api/course-description/'.$b.'?cid='.$cid);
        self::assertResponseStatusCodeSame(204);
        $this->assertOrder($client, $cid, [$c, $a, $d]);

        $this->move($client, $cid, $other, 'up', 403);
        $this->move($client, $cid, $a, 'invalid', 400);
        $this->assertOrder($client, $foreignId, [$other]);
        $learnerClient = $this->createClientWithCredentials($learnerToken);
        $items = $this->assertOrder($learnerClient, $cid, [$c, $a, $d]);
        self::assertSame([false, false, false], array_column($items, 'canReorder'));
        $this->move($learnerClient, $cid, $c, 'down', 403);
        $this->assertOrder($learnerClient, $cid, [$c, $a, $d]);
    }

    public function testSessionReorderingDoesNotModifyInheritedDescriptions(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $course = $this->createCourse('Session descriptions '.$suffix);
        $session = $this->createSession('Description order '.$suffix);
        $session->addCourse($course);
        self::getContainer()->get(SessionRepository::class)->update($session);
        $admin = $this->createUser('cd_order_admin_'.$suffix, '', '', 'ROLE_ADMIN');
        $cid = (int) $course->getId();
        $sid = (int) $session->getId();
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($admin));
        $a = $this->createDescription($client, $cid, 'Base A');
        $b = $this->createDescription($client, $cid, 'Base B');
        $s1 = $this->createDescription($client, $cid, 'Session A', 0, $sid);
        $s2 = $this->createDescription($client, $cid, 'Session B', 0, $sid);
        $baseBefore = $this->linkPositions($cid);
        $sessionBefore = $this->linkPositions($cid, $sid);
        $this->move($client, $cid, $s2, 'up', 200, $sid);
        self::assertSame($baseBefore, $this->linkPositions($cid));
        self::assertSame($sessionBefore[$s1], $this->linkPositions($cid, $sid)[$s2]);
        self::assertSame($sessionBefore[$s2], $this->linkPositions($cid, $sid)[$s1]);
        $items = $client->request('GET', '/api/course-description/list?cid='.$cid.'&sid='.$sid)->toArray()['items'];
        self::assertResponseIsSuccessful();
        self::assertSame([$s2, $s1], array_column(array_values(array_filter($items, static fn (array $item): bool => $item['canReorder'])), 'iid'));
        foreach ($items as $item) {
            if (\in_array($item['iid'], [$a, $b], true)) {
                self::assertFalse($item['canReorder']);
            }
        }
        $this->move($client, $cid, $a, 'down', 403, $sid);
        $this->move($client, $cid, $s1, 'up', 403);
        $this->assertOrder($client, $cid, [$a, $b]);
    }

    /**
     * @return array<int, int>
     */
    private function linkPositions(int $cid, int $sid = 0): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT d.iid, rl.display_order FROM c_course_description d
             INNER JOIN resource_link rl ON rl.resource_node_id = d.resource_node_id
             WHERE rl.c_id = :cid AND COALESCE(rl.session_id, 0) = :sid
               AND rl.group_id IS NULL AND rl.user_id IS NULL AND rl.usergroup_id IS NULL
               AND rl.deleted_at IS NULL ORDER BY d.iid',
            ['cid' => $cid, 'sid' => $sid]
        );

        return array_column(array_map(static fn (array $row): array => [
            'iid' => (int) $row['iid'],
            'display_order' => (int) $row['display_order'],
        ], $rows), 'display_order', 'iid');
    }

    private function createDescription(Client $client, int $cid, string $title, int $progress = 0, int $sid = 0): int
    {
        $response = $client->request('POST', '/api/course-description?cid='.$cid.'&sid='.$sid, [
            'json' => ['descriptionType' => 8, 'title' => $title, 'content' => '<p>Content '.$title.'</p>', 'progress' => $progress, 'language' => '', 'enableSearch' => false],
        ]);
        self::assertResponseStatusCodeSame(201);

        return (int) $response->toArray()['iid'];
    }

    private function move(Client $client, int $cid, int $iid, string $direction, int $status = 200, int $sid = 0): void
    {
        $client->request('POST', '/api/course-description/reorder?cid='.$cid.'&sid='.$sid, [
            'json' => ['iid' => $iid, 'direction' => $direction],
        ]);
        self::assertResponseStatusCodeSame($status);
    }

    private function assertOrder(Client $client, int $cid, array $ids): array
    {
        $response = $client->request('GET', '/api/course-description/list?cid='.$cid);
        self::assertResponseIsSuccessful();
        $items = $response->toArray()['items'];
        self::assertSame($ids, array_column($items, 'iid'));

        return $items;
    }
}
