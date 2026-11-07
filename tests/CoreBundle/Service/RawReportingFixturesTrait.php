<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\DBAL\Connection;

/**
 * Prepared-statement fixtures shared by the reporting service tests. The ORM/resource-node path deadlocks when
 * sibling suites run concurrently, so every row is a raw INSERT. Users and sessions are tagged "rp_". Course
 * fixtures are the installer courses 1 (AIACT) and 2 (USINGCHAMILO), both registered on access URL 1.
 */
trait RawReportingFixturesTrait
{
    private int $rpSeq = 0;

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function fresh(): void
    {
        $this->getEntityManager()->clear();
    }

    private function rawUser(string $suffix, int $status = 5, int $active = 1, ?int $urlId = 1): int
    {
        $name = 'rp_'.$suffix.'_'.getmypid().'_'.(++$this->rpSeq);
        $this->conn()->executeStatement(
            'INSERT INTO user (username, username_canonical, email, email_canonical, firstname, lastname, locale,
                password, timezone, locked, expired, credentials_expired, salt, roles, status, active, uuid,
                mfa_enabled, created_at, updated_at, official_code)
             VALUES (?, ?, ?, ?, ?, ?, \'en_US\', \'x\', \'UTC\', 0, 0, 0, \'x\', \'[]\', ?, ?,
                UNHEX(REPLACE(UUID(), \'-\', \'\')), 0, \'2020-01-01 00:00:00\', \'2020-01-01 00:00:00\', \'\')',
            [$name, $name, $name.'@example.com', $name.'@example.com', 'First'.$suffix, 'Last'.$suffix, $status, $active]
        );
        $id = (int) $this->conn()->lastInsertId();
        if (null !== $urlId) {
            $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$urlId, $id]);
        }

        return $id;
    }

    private function entityUser(int $id): User
    {
        return $this->getEntityManager()->getRepository(User::class)->find($id);
    }

    private function rawSession(string $title, ?int $urlId = 1, ?string $start = null, ?string $end = null): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status,
                access_start_date, access_end_date, position)
             VALUES (?, 0, 0, 0, 1, 0, ?, ?, 0)',
            ['rp_'.$title.'_'.getmypid().'_'.(++$this->rpSeq), $start, $end]
        );
        $id = (int) $this->conn()->lastInsertId();
        if (null !== $urlId) {
            $this->conn()->executeStatement('INSERT INTO access_url_rel_session (session_id, access_url_id) VALUES (?, ?)', [$id, $urlId]);
        }

        return $id;
    }

    private function sessionCourse(int $sid, int $cid): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course (session_id, c_id, position, nbr_users) VALUES (?, ?, 0, 0)',
            [$sid, $cid]
        );
    }

    private function sessionUser(int $sid, int $uid, int $relationType): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_user (session_id, user_id, relation_type, duration, registered_at)
             VALUES (?, ?, ?, 0, \'2020-01-01 00:00:00\')',
            [$sid, $uid, $relationType]
        );
    }

    private function sessionCourseUser(int $sid, int $cid, int $uid, int $status = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (user_id, session_id, c_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 1, 0, 0)',
            [$uid, $sid, $cid, $status]
        );
    }

    private function courseUser(int $uid, int $cid, int $status = 5, int $relationType = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, relation_type, status, is_tutor, sort, user_course_cat, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 0, 0, 0, 0, 0)',
            [$uid, $cid, $relationType, $status]
        );
    }

    private function userRel(int $userId, int $friendId, int $relationType): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO user_rel_user (user_id, friend_user_id, relation_type, created_at, updated_at)
             VALUES (?, ?, ?, \'2020-01-01 00:00:00\', \'2020-01-01 00:00:00\')',
            [$userId, $friendId, $relationType]
        );
    }

    /**
     * A second access URL; with $child it hangs below URL 1 (so its users are not "unrestricted").
     */
    private function secondUrl(bool $child = false): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO access_url (url, active, description, created_by, lft, rgt, lvl, parent_id, tree_root)
             VALUES (?, 1, \'\', 1, ?, ?, ?, ?, ?)',
            ['http://rp-second-url.invalid/'.getmypid().'/'.(++$this->rpSeq), $child ? 2 : 1, $child ? 3 : 2, $child ? 1 : 0, $child ? 1 : null, $child ? 1 : null]
        );
        $this->fresh();

        return (int) $this->conn()->lastInsertId();
    }
}
