<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Repository\CForumPostRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\UuidV4;

/**
 * "How many forum posts / publications did this user write in this course" must count what the user CREATED.
 *
 * Why: the counters used getResourcesByCourseLinkedToUser(), whose "links.user IS NULL" branch (right for the
 * announcements a student may SEE) matches every resource linked to the course without a user, so every user was
 * credited with all the course's posts and publications. Fixtures are prepared statements tagged "sw_" (the
 * repositories insert resource nodes and deadlock against concurrent suites), rolled back by DAMA.
 */
final class ResourceCreatedByUserTest extends KernelTestCase
{
    private const COURSE = 1;
    private const RT_POST = 28;
    private const RT_WORK = 6;

    private Connection $conn;
    private int $seq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::setSession(new Session(new MockArraySessionStorage()));
        $this->conn = $container->get('doctrine')->getConnection();
    }

    public function testForumPostsAreCreditedOnlyToTheirAuthor(): void
    {
        $u1 = $this->user();
        $u2 = $this->user();
        $u3 = $this->user();
        $this->post($u1);
        $this->post($u1);
        $this->post($u2);
        // A post linked to the course AND to a given user, written by somebody else, is not u3's own post.
        $this->post($u1, null, $u3);

        $repo = $this->forumPosts();
        $course = $this->course();
        $this->assertSame(3, $repo->countUserForumPosts($this->entity($u1), $course));
        $this->assertSame(1, $repo->countUserForumPosts($this->entity($u2), $course));
        $this->assertSame(0, $repo->countUserForumPosts($this->entity($u3), $course));
    }

    public function testForumPostsOfASessionAreNotCountedInTheBaseCourse(): void
    {
        $u1 = $this->user();
        $session = $this->session();
        $this->post($u1);
        $this->post($u1, $session);

        $course = $this->course();
        $this->assertSame(1, $this->forumPosts()->countUserForumPosts($this->entity($u1), $course));
        $this->assertSame(
            1,
            $this->forumPosts()->countUserForumPosts($this->entity($u1), $course, $this->sessionEntity($session))
        );
    }

    public function testPublicationsAreCreditedOnlyToTheirAuthor(): void
    {
        $u1 = $this->user();
        $u2 = $this->user();
        $assignment = $this->work($u1, null);
        $this->work($u1, $assignment);
        $this->work($u1, $assignment);
        $this->work($u2, $assignment);
        // Not a submission (no parent publication): never counted.
        $this->work($u2, null);

        $repo = $this->publications();
        $course = $this->course();
        $this->assertSame(2, $repo->countUserPublications($this->entity($u1), $course));
        $this->assertSame(1, $repo->countUserPublications($this->entity($u2), $course));
        $this->assertSame(0, $repo->countUserPublications($this->entity($this->user()), $course));
    }

    public function testTheVisibilityVariantStillMatchesWhatIsLinkedToNoUser(): void
    {
        // PINNED ON PURPOSE: getResourcesByCourseLinkedToUser() answers "what may this user see" for the
        // announcements (AnnouncementManager), where a resource linked to nobody is addressed to everyone.
        // Only the contribution counters moved to getResourcesByCourseCreatedByUser().
        $author = $this->user();
        $reader = $this->user();
        $this->post($author);

        $qb = $this->forumPosts()->getResourcesByCourseLinkedToUser($this->entity($reader), $this->course());
        $this->assertGreaterThanOrEqual(1, $this->forumPosts()->getCount($qb));
        $qb = $this->forumPosts()->getResourcesByCourseCreatedByUser($this->entity($reader), $this->course());
        $this->assertSame(0, $this->forumPosts()->getCount($qb));
    }

    // ---------------------------------------------------------------- fixtures

    private function forumPosts(): CForumPostRepository
    {
        return Container::getForumPostRepository();
    }

    private function publications(): CStudentPublicationRepository
    {
        return Container::getStudentPublicationRepository();
    }

    private function course(): Course
    {
        return self::getContainer()->get('doctrine')->getManager()->find(Course::class, self::COURSE);
    }

    private function entity(int $id): User
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        $em->clear(User::class);

        return $em->find(User::class, $id);
    }

    private function sessionEntity(int $id): \Chamilo\CoreBundle\Entity\Session
    {
        return self::getContainer()->get('doctrine')->getManager()->find(\Chamilo\CoreBundle\Entity\Session::class, $id);
    }

    private function user(): int
    {
        $name = 'sw_u'.(++$this->seq).'_'.substr(md5((string) microtime(true)), 0, 6);
        $this->conn->executeStatement(
            "INSERT INTO user (username, username_canonical, locale, password, timezone, email, email_canonical,
                locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at,
                firstname, lastname)
             VALUES (?, ?, 'en_US', 'x', 'UTC', ?, ?, 0, 0, 0, 'x', '[]', 5, 1, ?,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', ?, 'sw')",
            [$name, $name, $name.'@example.com', $name.'@example.com', UuidV4::v4()->toBinary(), $name]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function session(): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status) VALUES (?, 0, 0, 0, 1, 0)',
            ['sw_session_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function node(int $type, int $creator): int
    {
        $n = ++$this->seq;
        $this->conn->executeStatement(
            "INSERT INTO resource_node (resource_type_id, creator_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (?, ?, 'sw_node', ?, 0, ?, '2001-01-01 00:00:00', '2001-01-01 00:00:00', 0, ?)",
            [$type, $creator, 'sw-'.$n.'-'.substr(md5((string) microtime(true)), 0, 6), 'sw-'.$n.'/', UuidV4::v4()->toBinary()]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function link(int $node, ?int $session, ?int $linkUser): void
    {
        $this->conn->executeStatement(
            "INSERT INTO resource_link (resource_node_id, c_id, session_id, user_id, visibility, display_order,
                resource_type_group, created_at, updated_at)
             VALUES (?, ?, ?, ?, 2, 0, 0, '2001-01-01 00:00:00', '2001-01-01 00:00:00')",
            [$node, self::COURSE, $session, $linkUser]
        );
    }

    /**
     * A forum post written by $author (the resource node creator), optionally linked to a session or a user.
     */
    private function post(int $author, ?int $session = null, ?int $linkUser = null): void
    {
        $this->conn->executeStatement(
            "INSERT INTO c_forum_forum (title, locked, forum_image, resource_node_id) VALUES ('sw_forum', 0, '', ?)",
            [$forumNode = $this->node(25, $author)]
        );
        $forum = (int) $this->conn->lastInsertId();
        $this->link($forumNode, $session, null);
        $this->conn->executeStatement(
            "INSERT INTO c_forum_thread (forum_id, thread_poster_id, title, thread_date, thread_replies, thread_views,
                thread_sticky, locked, thread_qualify_max, thread_weight, thread_peer_qualify, resource_node_id)
             VALUES (?, ?, 'sw_thread', '2001-01-01 00:00:00', 0, 0, 0, 0, 0, 0, 0, ?)",
            [$forum, $author, $threadNode = $this->node(29, $author)]
        );
        $thread = (int) $this->conn->lastInsertId();
        $this->link($threadNode, $session, null);
        $this->conn->executeStatement(
            "INSERT INTO c_forum_post (thread_id, forum_id, poster_id, title, post_text, post_date, post_notification,
                visible, resource_node_id)
             VALUES (?, ?, ?, 'sw_post', 'x', '2001-01-01 00:00:00', 0, 1, ?)",
            [$thread, $forum, $author, $postNode = $this->node(self::RT_POST, $author)]
        );
        $this->link($postNode, $session, $linkUser);
    }

    private function work(int $author, ?int $parent): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_student_publication (parent_id, user_id, title, active, post_group_id, filetype,
                has_properties, qualification, qualificator_id, weight, allow_text_assignment, contains_file, document_id, resource_node_id)
             VALUES (?, ?, 'sw_work', 1, 0, 'file', 0, 0, 0, 0, 0, 0, 0, ?)",
            [$parent, $author, $node = $this->node(self::RT_WORK, $author)]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->link($node, null, null);

        return $id;
    }
}
