<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingContext;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingSectionQueryService;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use const SORT_STRING;

/**
 * Characterization of the learning-path-authors, learning-path-item-authors and works-in-session sections of
 * GlobalReportingSectionQueryService. Fixtures are "r3_" resources and "rp_" users, rolled back with the
 * transaction. The installer courses 1 and 2 are both on access URL 1.
 */
final class GlobalReportingAuthorsWorksTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use GenericRowInsertTrait;
    use RawReportingFixturesTrait;
    use ReportingResourceFixturesTrait;

    private GlobalReportingSectionQueryService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $httpSession = new HttpSession(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($httpSession);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        Container::setSession($httpSession);
        Container::setRequest($request);
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());
        $this->service = self::getContainer()->get(GlobalReportingSectionQueryService::class);
    }

    private function ctx(int $userId, bool $admin = false, bool $hr = false, int $urlId = 1): GlobalReportingContext
    {
        $this->fresh();

        return new GlobalReportingContext(
            $this->entityUser($userId),
            $urlId,
            $admin,
            $hr,
            false,
            true,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function section(GlobalReportingContext $ctx, string $name, array $filters = []): array
    {
        return $this->service->getSection($ctx, $name, $filters);
    }

    private function lpBy(string $title, string $author, int $courseId = 1, ?int $sessionId = null): int
    {
        $lp = $this->newLp($title, $courseId, $sessionId);
        $this->conn()->executeStatement('UPDATE c_lp SET author = ? WHERE iid = ?', [$author, $lp]);

        return $lp;
    }

    // -------------------------------------------------- learning path authors

    public function testLearningPathAuthorsCountThePathsOfTheCoursesOfTheCurrentUrl(): void
    {
        $this->conn()->executeStatement('DELETE FROM access_url_rel_course WHERE c_id = 2');
        $before = array_column($this->section($this->ctx(1, true), 'learning-path-authors')['items'], 'items', 'author');
        $this->lpBy('r3_a1', ' R3 Author One ');
        $this->lpBy('r3_a2', 'R3 Author One');
        $this->lpBy('r3_a3', '');
        $this->lpBy('r3_a5', 'R3 Author Hidden', 2);

        $result = $this->section($this->ctx(1, true), 'learning-path-authors');

        $byAuthor = array_column($result['items'], null, 'author');
        $this->assertSame(2, $byAuthor['R3 Author One']['items'], 'authors are trimmed before grouping');
        $this->assertSame('AI Act', $byAuthor['R3 Author One']['courses']);
        $this->assertSame(($before['Unknown'] ?? 0) + 1, $byAuthor['Unknown']['items'], 'an empty author is "Unknown"');
        $this->assertArrayNotHasKey('R3 Author Hidden', $byAuthor, 'courses of other URLs are out of scope');
        $authors = array_column($result['items'], 'author');
        $sorted = $authors;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $authors, 'alphabetical');
    }

    public function testLearningPathCountsEachPathOnceEvenWhenItIsLinkedToSeveralContexts(): void
    {
        $lp = $this->lpBy('r3_multi', 'R3 Multi Author');
        $session = $this->rawSession('r3mu1');
        $node = (int) $this->conn()->fetchOne('SELECT resource_node_id FROM c_lp WHERE iid = ?', [$lp]);
        $this->link($node, 1, $session);
        $this->lpBy('r3_single', 'R3 Multi Author');

        $row = array_column($this->section($this->ctx(1, true), 'learning-path-authors')['items'], null, 'author')['R3 Multi Author'];

        $this->assertSame(2, $row['items'], 'two learning paths, one of them linked twice');
    }

    /**
     * PINNED QUIRK: the section itself accepts human resources managers (assertAdministratorOrHumanResources), but the
     * outer section gate lists it as administrator-only, so the HR branch is unreachable. Kept: the stricter gate wins.
     */
    public function testLearningPathAuthorsAreAdministratorOnlyInPractice(): void
    {
        $hr = $this->rawUser('r3ah1', 4);
        $teacher = $this->rawUser('r3ah2', 1);

        foreach ([$this->ctx($hr, false, true), $this->ctx($teacher)] as $ctx) {
            try {
                $this->section($ctx, 'learning-path-authors');
                $this->fail('served to a non administrator');
            } catch (AccessDeniedHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testLearningPathItemAuthorsGroupTheItemsByTheAuthorExtraField(): void
    {
        $this->conn()->executeStatement('DELETE FROM extra_field_values WHERE field_id IN (SELECT id FROM extra_field WHERE variable = \'authorlpitem\')');
        $this->conn()->executeStatement('DELETE FROM extra_field WHERE variable = \'authorlpitem\'');
        $this->insert('extra_field', ['item_type' => 0, 'value_type' => 1, 'variable' => 'authorlpitem']);
        $field = (int) $this->conn()->lastInsertId();
        $lp = $this->lpBy('r3_items', 'someone');
        $items = [];
        foreach ([' R3 Item Author ', 'R3 Item Author', null] as $i => $author) {
            $this->insert('c_lp_item', ['lp_id' => $lp, 'title' => 'r3_item'.$i, 'item_type' => 'document', 'ref' => '', 'path' => '', 'launch_data' => '']);
            $items[] = (int) $this->conn()->lastInsertId();
            if (null !== $author) {
                $this->insert('extra_field_values', ['field_id' => $field, 'item_id' => $items[$i], 'field_value' => $author]);
            }
        }

        $result = $this->section($this->ctx(1, true), 'learning-path-item-authors');

        $byAuthor = array_column($result['items'], null, 'author');
        $this->assertSame(2, $byAuthor['R3 Item Author']['items']);
        $this->assertGreaterThanOrEqual(1, $byAuthor['Unknown']['items'], 'items without a value');
    }

    // ------------------------------------------------------ works in session

    private function work(string $title, int $courseId, int $sessionId, string $author, string $sentAt, ?int $parent): int
    {
        $node = $this->node('student_publications', $title);
        $this->insert('c_student_publication', ['resource_node_id' => $node, 'parent_id' => $parent, 'title' => $title, 'author' => $author, 'sent_date' => $sentAt, 'qualification' => 12.5, 'weight' => 20, 'accepted' => 1]);
        $id = (int) $this->conn()->lastInsertId();
        $this->link($node, $courseId, $sessionId);

        return $id;
    }

    public function testWorksInSessionListTheWorksLinkedToTheSessionsOfTheUrlNewestFirst(): void
    {
        $this->insert('c_student_publication', ['title' => 'r3_assignment']);
        $assignment = (int) $this->conn()->lastInsertId();
        $s1 = $this->rawSession('r3ws1');
        $s2 = $this->rawSession('r3ws2');
        $foreign = $this->rawSession('r3ws3', null);
        $old = $this->work('r3_work_old', 1, $s1, 'Learner A', '2001-01-01 10:00:00', $assignment);
        $new = $this->work('r3_work_new', 1, $s1, 'Learner B', '2001-01-02 10:00:00', $assignment);
        $other = $this->work('r3_work_s2', 2, $s2, 'Learner C', '2001-01-03 10:00:00', $assignment);
        $this->work('r3_work_assignment_root', 1, $s1, 'Learner D', '2001-01-04 10:00:00', null);
        $this->work('r3_work_foreign', 1, $foreign, 'Learner E', '2001-01-05 10:00:00', $assignment);
        $admin = $this->ctx(1, true);

        $result = $this->section($admin, 'works-in-session', ['sessionId' => $s1]);
        $this->assertSame([$new, $old], array_column($result['items'], 'id'));
        $this->assertSame(['Learner B', 'Learner A'], array_column($result['items'], 'author'));
        $this->assertSame(12.5, $result['items'][0]['qualification']);
        $this->assertSame('Yes', $result['items'][0]['accepted']);
        $this->assertSame('AI Act', $result['items'][0]['course']);

        $all = $this->section($admin, 'works-in-session');
        $ids = array_column($all['items'], 'id');
        $this->assertContains($other, $ids);
        $this->assertNotContains($this->conn()->fetchOne('SELECT iid FROM c_student_publication WHERE title = ?', ['r3_work_foreign']), $ids, 'sessions outside the URL');
        $this->assertLessThan(array_search($other, $ids, true), array_search($new, $ids, true), 'ordered by session title then course');

        $this->expectException(AccessDeniedHttpException::class);
        $this->section($admin, 'works-in-session', ['sessionId' => $foreign]);
    }

    public function testWorksInSessionOfACoachAreLimitedToTheirOwnSessions(): void
    {
        $this->insert('c_student_publication', ['title' => 'r3_assignment2']);
        $assignment = (int) $this->conn()->lastInsertId();
        $mine = $this->rawSession('r3ws4');
        $theirs = $this->rawSession('r3ws5');
        $coach = $this->rawUser('r3ws6', 1);
        $this->sessionUser($mine, $coach, 3);
        $a = $this->work('r3_work_mine', 1, $mine, 'Learner M', '2001-02-01 10:00:00', $assignment);
        $b = $this->work('r3_work_theirs', 1, $theirs, 'Learner T', '2001-02-02 10:00:00', $assignment);

        $result = $this->section($this->ctx($coach), 'works-in-session');

        $this->assertSame([$a], array_column($result['items'], 'id'));
        $this->assertNotContains($b, array_column($result['items'], 'id'));
        $this->expectException(AccessDeniedHttpException::class);
        $this->section($this->ctx($coach), 'works-in-session', ['sessionId' => $theirs]);
    }
}
