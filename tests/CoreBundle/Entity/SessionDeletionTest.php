<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * Every session deletion path (admin session list, API, SessionManager::delete()) removes the session through the
 * ORM. A resource link is soft-deletable, so removing it along with the session only stamped it as deleted, and that
 * re-persisted the session: the request reported "Sessions deleted." while the session stayed.
 */
class SessionDeletionTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testDeletingASessionWithCourseResourcesRemovesTheSessionAndOnlyItsLinks(): void
    {
        $em = $this->getEntityManager();
        $course = $this->createCourse('Session deletion course');
        $session = $this->createSession('Session deletion with resources');
        $admin = $this->getUser('admin');

        $assignment = (new CStudentPublication())
            ->setTitle('Assignment of the course and of the session')
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($admin)
            ->setUser($admin)
            ->setActive(1)
            ->addCourseLink($course)
            ->addCourseLink($course, $session)
        ;
        $em->persist($assignment);
        $em->flush();

        $sessionId = (int) $session->getId();
        $nodeId = (int) $assignment->getResourceNode()->getId();
        $connection = $em->getConnection();
        $this->assertSame(
            1,
            (int) $connection->fetchOne('SELECT COUNT(*) FROM resource_link WHERE session_id = ?', [$sessionId]),
        );

        // Loaded fresh, as a deletion request does: the session's links collection is then read from the database.
        $em->clear();
        $em->remove($em->find(Session::class, $sessionId));
        $em->flush();
        $em->clear();

        $this->assertSame(
            0,
            (int) $connection->fetchOne('SELECT COUNT(*) FROM session WHERE id = ?', [$sessionId]),
            'The session must be gone, not only reported as deleted.',
        );
        $this->assertSame(
            0,
            (int) $connection->fetchOne('SELECT COUNT(*) FROM resource_link WHERE session_id = ?', [$sessionId]),
            'The links of the session go with it.',
        );
        $this->assertSame(
            1,
            (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM resource_link WHERE resource_node_id = ? AND session_id IS NULL AND deleted_at IS NULL',
                [$nodeId],
            ),
            'The base course keeps its own link to the resource.',
        );
    }
}
