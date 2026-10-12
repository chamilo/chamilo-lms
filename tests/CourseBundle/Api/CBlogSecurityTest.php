<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CBlog;
use Chamilo\CourseBundle\Entity\CBlogComment;
use Chamilo\CourseBundle\Entity\CBlogPost;
use Chamilo\CourseBundle\Entity\CBlogRelUser;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * Regression tests for the cross-course blog access advisory.
 *
 * The advisory reported that authenticated users outside the course/session
 * scope could read and modify blog resources via:
 *   - GET    /api/c_blogs/{iid}
 *   - PATCH  /api/c_blogs/{iid}
 *   - POST   /api/c_blog_posts
 *   - POST   /api/c_blog_comments
 *   - POST   /api/c_blog_rel_users
 *
 * These tests assert that an unrelated authenticated user (the attacker) is
 * denied (403), while a course teacher / enrolled student still works.
 */
final class CBlogSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * Builds a private (REGISTERED) course, subscribes a teacher and a student,
     * creates a blog inside it owned by the teacher, plus one post and one
     * comment authored by the teacher.
     *
     * @return array{
     *     course: Course,
     *     teacher: User,
     *     student: User,
     *     attacker: User,
     *     blog: CBlog,
     *     post: CBlogPost,
     *     comment: CBlogComment
     * }
     */
    private function bootstrapBlogScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Blog Sec Course '.$suffix);
        // REGISTERED → only enrolled users gain ROLE_CURRENT_COURSE_* via CourseVoter.
        $course->setVisibility(Course::REGISTERED);

        $teacher = $this->createUser('blog_sec_teacher_'.$suffix);
        $student = $this->createUser('blog_sec_student_'.$suffix);
        $attacker = $this->createUser('blog_sec_attacker_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $courseRepo->update($course);

        $em = $this->getEntityManager();

        $blog = (new CBlog())
            ->setTitle('Victim Blog '.$suffix)
            ->setBlogSubtitle('subtitle')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($blog);
        $em->flush();

        $post = (new CBlogPost())
            ->setTitle('Victim Post '.$suffix)
            ->setFullText('Victim post body')
            ->setBlog($blog)
            ->setAuthor($teacher)
        ;
        $em->persist($post);

        $comment = (new CBlogComment())
            ->setTitle('Victim Comment '.$suffix)
            ->setComment('Victim comment body')
            ->setBlog($blog)
            ->setPost($post)
            ->setAuthor($teacher)
        ;
        $em->persist($comment);

        $em->flush();

        return [
            'course' => $course,
            'teacher' => $teacher,
            'student' => $student,
            'attacker' => $attacker,
            'blog' => $blog,
            'post' => $post,
            'comment' => $comment,
        ];
    }

    // -------------------------------------------------------------------------
    // GET /api/c_blogs/{iid}
    // -------------------------------------------------------------------------

    public function testGetBlogAsEnrolledTeacherIsAllowed(): void
    {
        $ctx = $this->bootstrapBlogScenario('get_teacher');

        $token = $this->getUserTokenFromUser($ctx['teacher']);

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/c_blogs/'.$ctx['blog']->getIid().'?cid='.$ctx['course']->getId(),
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@type' => 'CBlog',
            'title' => 'Victim Blog get_teacher',
        ]);
    }

    public function testGetBlogAsForeignUserIsForbidden(): void
    {
        $ctx = $this->bootstrapBlogScenario('get_foreign');

        $token = $this->getUserTokenFromUser($ctx['attacker']);

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/c_blogs/'.$ctx['blog']->getIid(),
        );

        // Voter denies VIEW because attacker has no matching ResourceLink.
        $this->assertResponseStatusCodeSame(403);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/c_blogs/{iid}
    // -------------------------------------------------------------------------

    public function testPatchBlogAsForeignUserIsForbidden(): void
    {
        $ctx = $this->bootstrapBlogScenario('patch_foreign');

        $token = $this->getUserTokenFromUser($ctx['attacker']);

        $this->createClientWithCredentials($token)->request(
            'PATCH',
            '/api/c_blogs/'.$ctx['blog']->getIid(),
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'json' => ['title' => 'Attacker edited blog'],
            ]
        );

        $this->assertResponseStatusCodeSame(403);

        $em = $this->getEntityManager();
        $em->clear();
        $fresh = $em->getRepository(CBlog::class)->find($ctx['blog']->getIid());
        // The blog title must remain untouched.
        $this->assertSame('Victim Blog patch_foreign', $fresh?->getTitle());
    }

    public function testPatchBlogAsEnrolledStudentIsForbidden(): void
    {
        $ctx = $this->bootstrapBlogScenario('patch_student');

        $token = $this->getUserTokenFromUser($ctx['student']);

        $this->createClientWithCredentials($token)->request(
            'PATCH',
            '/api/c_blogs/'.$ctx['blog']->getIid(),
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'json' => ['title' => 'Student edited blog'],
            ]
        );

        // Patch requires EDIT, students only have VIEW.
        $this->assertResponseStatusCodeSame(403);
    }

    public function testPatchBlogAsEnrolledTeacherIsAllowed(): void
    {
        $ctx = $this->bootstrapBlogScenario('patch_teacher');

        $token = $this->getUserTokenFromUser($ctx['teacher']);

        $this->createClientWithCredentials($token)->request(
            'PATCH',
            '/api/c_blogs/'.$ctx['blog']->getIid().'?cid='.$ctx['course']->getId(),
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'json' => ['title' => 'Teacher edited blog'],
            ]
        );

        $this->assertResponseStatusCodeSame(200);
    }

    // -------------------------------------------------------------------------
    // POST /api/c_blog_posts
    // -------------------------------------------------------------------------

    public function testPostBlogPostAsForeignUserIsForbidden(): void
    {
        $ctx = $this->bootstrapBlogScenario('post_foreign');

        $token = $this->getUserTokenFromUser($ctx['attacker']);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_blog_posts',
            [
                'json' => [
                    'title' => 'attacker post',
                    'fullText' => 'cross-blog write',
                    'blog' => '/api/c_blogs/'.$ctx['blog']->getIid(),
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(403);

        $em = $this->getEntityManager();
        $em->clear();
        $count = $em->getRepository(CBlogPost::class)
            ->count(['blog' => $ctx['blog']->getIid()])
        ;
        // Only the bootstrapped victim post must exist.
        $this->assertSame(1, $count);
    }

    public function testPostBlogPostAsEnrolledTeacherIsAllowed(): void
    {
        $ctx = $this->bootstrapBlogScenario('post_teacher');

        $token = $this->getUserTokenFromUser($ctx['teacher']);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_blog_posts?cid='.$ctx['course']->getId(),
            [
                'json' => [
                    'title' => 'legit post',
                    'fullText' => 'legit body',
                    'blog' => '/api/c_blogs/'.$ctx['blog']->getIid(),
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(201);
    }

    // -------------------------------------------------------------------------
    // POST /api/c_blog_rel_users
    // -------------------------------------------------------------------------

    public function testPostBlogRelUserAsForeignUserIsForbidden(): void
    {
        $ctx = $this->bootstrapBlogScenario('rel_foreign');

        $token = $this->getUserTokenFromUser($ctx['attacker']);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_blog_rel_users',
            [
                'json' => [
                    'blog' => '/api/c_blogs/'.$ctx['blog']->getIid(),
                    'user' => '/api/users/'.$ctx['attacker']->getId(),
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(403);

        $em = $this->getEntityManager();
        $em->clear();
        $count = $em->getRepository(CBlogRelUser::class)
            ->count(['blog' => $ctx['blog']->getIid()])
        ;
        $this->assertSame(0, $count);
    }

    public function testPostBlogRelUserAsEnrolledStudentIsForbidden(): void
    {
        $ctx = $this->bootstrapBlogScenario('rel_student');

        $token = $this->getUserTokenFromUser($ctx['student']);

        // Membership management requires EDIT on the blog; students only have VIEW.
        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_blog_rel_users',
            [
                'json' => [
                    'blog' => '/api/c_blogs/'.$ctx['blog']->getIid(),
                    'user' => '/api/users/'.$ctx['student']->getId(),
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPostBlogRelUserAsEnrolledTeacherIsAllowed(): void
    {
        $ctx = $this->bootstrapBlogScenario('rel_teacher');

        $token = $this->getUserTokenFromUser($ctx['teacher']);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_blog_rel_users?cid='.$ctx['course']->getId(),
            [
                'json' => [
                    'blog' => '/api/c_blogs/'.$ctx['blog']->getIid(),
                    'user' => '/api/users/'.$ctx['student']->getId(),
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(201);
    }

    // -------------------------------------------------------------------------
    // POST /api/c_blog_comments
    // -------------------------------------------------------------------------

    public function testPostBlogCommentAsForeignUserIsForbidden(): void
    {
        $ctx = $this->bootstrapBlogScenario('comm_foreign');

        $token = $this->getUserTokenFromUser($ctx['attacker']);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_blog_comments',
            [
                'json' => [
                    'title' => 'attacker comment',
                    'comment' => 'cross-blog comment',
                    'blog' => '/api/c_blogs/'.$ctx['blog']->getIid(),
                    'post' => '/api/c_blog_posts/'.$ctx['post']->getIid(),
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(403);

        $em = $this->getEntityManager();
        $em->clear();
        $count = $em->getRepository(CBlogComment::class)
            ->count(['post' => $ctx['post']->getIid()])
        ;
        // Only the bootstrapped victim comment must exist.
        $this->assertSame(1, $count);
    }

    public function testPostBlogCommentAsEnrolledStudentIsAllowed(): void
    {
        $ctx = $this->bootstrapBlogScenario('comm_student');

        $token = $this->getUserTokenFromUser($ctx['student']);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_blog_comments?cid='.$ctx['course']->getId(),
            [
                'json' => [
                    'title' => 'student comment',
                    'comment' => 'student comment body',
                    'blog' => '/api/c_blogs/'.$ctx['blog']->getIid(),
                    'post' => '/api/c_blog_posts/'.$ctx['post']->getIid(),
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(201);
    }

    // -------------------------------------------------------------------------
    // Admin bypass
    // -------------------------------------------------------------------------

    public function testGetBlogAsAdminIsAllowed(): void
    {
        $ctx = $this->bootstrapBlogScenario('get_admin');

        // Default token returned by getUserToken() is admin/admin.
        $this->createClientWithCredentials()->request(
            'GET',
            '/api/c_blogs/'.$ctx['blog']->getIid(),
        );

        $this->assertResponseStatusCodeSame(200);
    }
}
