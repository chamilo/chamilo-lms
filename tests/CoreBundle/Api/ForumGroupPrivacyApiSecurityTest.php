<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Repository\CForumPostRepository;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * The forum of a group is confined by the group's own forum_state setting, and the API is the
 * only place that can enforce it: GroupVoter matches the request URI against /main/ and
 * /resources/ paths, so no /api/ call ever reaches it and an unmatched path is treated as public.
 *
 * Without the check in the forum state layer, any student of the course reads and writes the
 * private forum of a group they do not belong to, by adding gid=<group> to the request.
 */
final class ForumGroupPrivacyApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testNonMemberCannotReadPrivateGroupForum(): void
    {
        $ctx = $this->bootstrapGroupForum(CGroup::TOOL_PRIVATE);

        $this->assertForumReadStatus($ctx, $ctx['outsider'], 403);
    }

    public function testMemberReadsPrivateGroupForum(): void
    {
        $ctx = $this->bootstrapGroupForum(CGroup::TOOL_PRIVATE);

        $this->assertForumReadStatus($ctx, $ctx['member'], 200);
    }

    public function testNonMemberReadsPublicGroupForum(): void
    {
        $ctx = $this->bootstrapGroupForum(CGroup::TOOL_PUBLIC);

        $this->assertForumReadStatus($ctx, $ctx['outsider'], 200);
    }

    public function testTeacherReadsPrivateGroupForum(): void
    {
        $ctx = $this->bootstrapGroupForum(CGroup::TOOL_PRIVATE);

        $this->assertForumReadStatus($ctx, $ctx['teacher'], 200);
    }

    /**
     * A tool turned off is off for everybody, members included. WikiHelper answers the same way.
     */
    public function testMemberCannotReadUnavailableGroupForum(): void
    {
        $ctx = $this->bootstrapGroupForum(CGroup::TOOL_NOT_AVAILABLE);

        $this->assertForumReadStatus($ctx, $ctx['member'], 403);
    }

    public function testNonMemberCannotReplyInPrivateGroupForum(): void
    {
        $ctx = $this->bootstrapGroupForum(CGroup::TOOL_PRIVATE);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['outsider']))->request(
            'POST',
            '/api/forum_posts/reply'.$this->contextQuery($ctx),
            [
                'json' => [
                    'forumId' => $ctx['forum']->getIid(),
                    'threadId' => $ctx['thread']->getIid(),
                    'title' => 'Intruder',
                    'text' => 'Written by a non member',
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testMemberRepliesInPrivateGroupForum(): void
    {
        $ctx = $this->bootstrapGroupForum(CGroup::TOOL_PRIVATE);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['member']))->request(
            'POST',
            '/api/forum_posts/reply'.$this->contextQuery($ctx),
            [
                'json' => [
                    'forumId' => $ctx['forum']->getIid(),
                    'threadId' => $ctx['thread']->getIid(),
                    'title' => 'Member reply',
                    'text' => 'Written by a group member',
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(201);
    }

    /**
     * Search reaches the same content through its own provider, which never resolves the group.
     */
    public function testNonMemberCannotFindPrivateGroupForumContent(): void
    {
        $ctx = $this->bootstrapGroupForum(CGroup::TOOL_PRIVATE);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['outsider']))->request(
            'GET',
            '/api/forum/search'.$this->contextQuery($ctx).'&q=CONFIDENTIAL'
        );
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Reads the three endpoints the Vue client uses to render a group forum: the forum list, the
     * thread list and the posts of a thread.
     *
     * @param array<string, mixed> $ctx
     */
    private function assertForumReadStatus(array $ctx, User $user, int $expected): void
    {
        $token = $this->getUserTokenFromUser($user);
        $query = $this->contextQuery($ctx);
        $parentNodeId = $ctx['forum']->getResourceNode()?->getParent()?->getId();

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/forums'.$query.'&resourceNode_parent='.$parentNodeId
        );
        $this->assertResponseStatusCodeSame($expected);

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/forum_threads'.$query.'&forum=/api/forums/'.$ctx['forum']->getIid()
        );
        $this->assertResponseStatusCodeSame($expected);

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/forum_threads/'.$ctx['thread']->getIid().'/posts'.$query.'&forumId='.$ctx['forum']->getIid()
        );
        $this->assertResponseStatusCodeSame($expected);
    }

    /**
     * @param array<string, mixed> $ctx
     */
    private function contextQuery(array $ctx): string
    {
        return '?cid='.$ctx['course']->getId().'&gid='.$ctx['group']->getIid();
    }

    /**
     * Course with a group whose forum carries one thread and one post. The teacher owns the
     * content, one student belongs to the group and another one only to the course.
     *
     * @return array<string, mixed>
     */
    private function bootstrapGroupForum(int $forumState): array
    {
        $suffix = uniqid();
        $em = $this->getEntityManager();
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $forumRepo = self::getContainer()->get(CForumRepository::class);
        $threadRepo = self::getContainer()->get(CForumThreadRepository::class);
        $postRepo = self::getContainer()->get(CForumPostRepository::class);

        $teacher = $this->createUser('fgp_teacher_'.$suffix, '', '', 'ROLE_TEACHER');
        $member = $this->createUser('fgp_member_'.$suffix);
        $outsider = $this->createUser('fgp_outsider_'.$suffix);

        $course = $this->createCourse('Forum group privacy '.$suffix);
        $course->setVisibility(Course::REGISTERED);
        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($member);
        $course->addUserAsStudent($outsider);
        $courseRepo->update($course);

        $group = $this->createGroup('Forum group '.$suffix, $course);
        $group->setForumState($forumState);
        $em->persist(
            (new CGroupRelUser())
                ->setStatus(1)
                ->setUser($member)
                ->setGroup($group)
                ->setRole('member')
                ->setCId((int) $course->getId())
        );
        $em->flush();

        $forum = (new CForum())
            ->setTitle('Group forum '.$suffix)
            ->setForumOfGroup((string) $group->getIid())
            ->setForumGroupPublicPrivate(CGroup::TOOL_PUBLIC === $forumState ? 'public' : 'private')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course, null, $group)
        ;
        $forumRepo->create($forum);

        $thread = (new CForumThread())
            ->setTitle('Group thread '.$suffix)
            ->setForum($forum)
            ->setThreadDate(new DateTime())
            ->setUser($teacher)
            ->setParent($forum)
            ->setCreator($teacher)
            ->addCourseLink($course, null, $group)
        ;
        $threadRepo->create($thread);

        $post = (new CForumPost())
            ->setTitle('Group post '.$suffix)
            ->setPostText('CONFIDENTIAL group content')
            ->setThread($thread)
            ->setForum($forum)
            ->setUser($teacher)
            ->setPostDate(new DateTime())
            ->setVisible(true)
            ->setStatus(CForumPost::STATUS_VALIDATED)
            ->setParent($thread)
            ->setCreator($teacher)
            ->addCourseLink($course, null, $group)
        ;
        $postRepo->create($post);

        return [
            'course' => $course,
            'group' => $group,
            'forum' => $forum,
            'thread' => $thread,
            'teacher' => $teacher,
            'member' => $member,
            'outsider' => $outsider,
        ];
    }
}
