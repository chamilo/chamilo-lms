<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

/**
 * Raw-INSERT fixtures for course resources (learning paths, tests, groups, documents...) used by the course
 * reporting tests. Needs RawReportingFixturesTrait and GenericRowInsertTrait in the same class. Resource nodes are
 * inserted directly (the ORM path deadlocks when sibling suites run), tagged "r3_" through their title.
 */
trait ReportingResourceFixturesTrait
{
    private function resourceTypeId(string $title): int
    {
        return (int) $this->conn()->fetchOne('SELECT id FROM resource_type WHERE title = ?', [$title]);
    }

    private function node(string $type, string $title, ?int $creator = 1, ?int $parent = null): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO resource_node (resource_type_id, creator_id, parent_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (?, ?, ?, ?, ?, 1, ?, '2001-01-01 00:00:00', '2001-01-01 00:00:00', 0, UNHEX(REPLACE(UUID(), '-', '')))",
            [$this->resourceTypeId($type), $creator, $parent, $title, 'r3-'.getmypid().'-'.(++$this->rpSeq), 'r3-'.$this->rpSeq]
        );

        return (int) $this->conn()->lastInsertId();
    }

    /**
     * @return int the resource_link id
     */
    private function link(int $node, int $courseId, ?int $sessionId = null, string $created = '2001-01-01 00:00:00', ?string $deleted = null): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, session_id, visibility, display_order, resource_type_group, created_at, updated_at, deleted_at)
             VALUES (?, ?, ?, 2, 0, 0, ?, ?, ?)',
            [$node, $courseId, $sessionId, $created, $created, $deleted]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function newLp(string $title, int $courseId = 1, ?int $sessionId = null): int
    {
        $node = $this->node('lps', $title);
        $this->insert('c_lp', ['resource_node_id' => $node, 'title' => $title, 'lp_type' => 1, 'path' => '']);
        $id = (int) $this->conn()->lastInsertId();
        $this->link($node, $courseId, $sessionId);

        return $id;
    }

    private function newQuiz(string $title, int $courseId = 1, ?int $sessionId = null): int
    {
        $node = $this->node('exercises', $title);
        $this->insert('c_quiz', ['resource_node_id' => $node, 'title' => $title, 'page_result_configuration' => '[]']);
        $id = (int) $this->conn()->lastInsertId();
        $this->link($node, $courseId, $sessionId);

        return $id;
    }

    private function newGroup(string $title, int $courseId = 1, ?int $sessionId = null): int
    {
        $node = $this->node('groups', $title);
        $this->insert('c_group_info', ['resource_node_id' => $node, 'title' => $title]);
        $id = (int) $this->conn()->lastInsertId();
        $this->link($node, $courseId, $sessionId);

        return $id;
    }

    private function groupMember(int $groupId, int $userId, int $courseId = 1): void
    {
        $this->insert('c_group_rel_user', ['group_id' => $groupId, 'user_id' => $userId, 'c_id' => $courseId]);
    }

    /**
     * @return int the c_lp_view iid
     */
    private function lpView(int $userId, int $lpId, int $progress, ?int $sessionId = null, int $viewCount = 1, int $courseId = 1): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress) VALUES (?, ?, ?, ?, ?, 0, ?)',
            [$userId, $lpId, $courseId, $sessionId, $viewCount, $progress]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function lpItemView(int $lpViewId, int $itemId, float $score, string $max, int $totalTime = 0, int $start = 0, string $status = 'completed'): void
    {
        $this->insert('c_lp_item_view', [
            'lp_view_id' => $lpViewId,
            'lp_item_id' => $itemId,
            'score' => $score,
            'max_score' => $max,
            'total_time' => $totalTime,
            'start_time' => $start,
            'status' => $status,
        ]);
    }

    private function exerciseAttempt(int $userId, int $quizId, float $score, float $max, ?int $sessionId = null, int $courseId = 1, string $date = '2001-01-01 10:00:00', int $lpId = 0): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_exercises (exe_user_id, c_id, session_id, exe_exo_id, exe_date, score, max_score, user_ip, status, data_tracking,
                start_date, steps_counter, orig_lp_id, orig_lp_item_id, exe_duration, orig_lp_item_view_id, questions_to_check)
             VALUES (?, ?, ?, ?, ?, ?, ?, '', '', '', '2001-01-01 09:00:00', 0, ?, 0, 0, 0, '')",
            [$userId, $courseId, $sessionId, $quizId, $date, $score, $max, $lpId]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function courseVisit(int $userId, string $in, ?string $out, int $courseId = 1, int $sessionId = 0): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, ?, ?, ?, 1, ?, '10.0.0.1')",
            [$userId, $courseId, $in, $out, $sessionId]
        );
    }
}
