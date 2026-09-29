<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
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

    /**
     * Builds one base-course blog and one blog in each of two sessions.
     *
     * @return array{
     *     course: Course,
     *     baseStudent: User,
     *     sessionOneStudent: User,
     *     sessionTwoStudent: User,
     *     sessionOneCoach: User,
     *     sessionOne: Session,
     *     sessionTwo: Session,
     *     baseBlog: CBlog,
     *     sessionOneBlog: CBlog,
     *     sessionTwoBlog: CBlog
     * }
     */
    private function bootstrapBlogSessionScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        /** @var SessionRepository $sessionRepo */
        $sessionRepo = self::getContainer()->get(SessionRepository::class);

        $course = $this->createCourse('Blog Session Course '.$suffix);
        $course->setVisibility(Course::REGISTERED);

        $baseStudent = $this->createUser('blog_base_student_'.$suffix);
        $sessionOneStudent = $this->createUser('blog_session_one_student_'.$suffix);
        $sessionTwoStudent = $this->createUser('blog_session_two_student_'.$suffix);
        $sessionOneCoach = $this->createUser('blog_session_one_coach_'.$suffix, '', '', 'ROLE_TEACHER');

        $course->addUserAsStudent($baseStudent);
        $courseRepo->update($course);

        $sessionOne = $this->createSession('Blog Session One '.$suffix);
        $sessionTwo = $this->createSession('Blog Session Two '.$suffix);

        $sessionOne->addCourse($course);
        $sessionTwo->addCourse($course);
        $sessionRepo->update($sessionOne);
        $sessionRepo->update($sessionTwo);

        $sessionRepo->addUserInCourse(Session::STUDENT, $sessionOneStudent, $course, $sessionOne);
        $sessionRepo->addUserInCourse(Session::STUDENT, $sessionTwoStudent, $course, $sessionTwo);
        $sessionRepo->addUserInCourse(Session::COURSE_COACH, $sessionOneCoach, $course, $sessionOne);
        $sessionRepo->update($sessionOne);
        $sessionRepo->update($sessionTwo);

        $em = $this->getEntityManager();
        $admin = $this->getUser('admin');

        $baseBlog = (new CBlog())
            ->setTitle('Base Blog '.$suffix)
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;
        $sessionOneBlog = (new CBlog())
            ->setTitle('Session One Blog '.$suffix)
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course, $sessionOne)
        ;
        $sessionTwoBlog = (new CBlog())
            ->setTitle('Session Two Blog '.$suffix)
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course, $sessionTwo)
        ;

        $em->persist($baseBlog);
        $em->persist($sessionOneBlog);
        $em->persist($sessionTwoBlog);
        $em->flush();

        return [
            'course' => $course,
            'baseStudent' => $baseStudent,
            'sessionOneStudent' => $sessionOneStudent,
            'sessionTwoStudent' => $sessionTwoStudent,
            'sessionOneCoach' => $sessionOneCoach,
            'sessionOne' => $sessionOne,
            'sessionTwo' => $sessionTwo,
            'baseBlog' => $baseBlog,
            'sessionOneBlog' => $sessionOneBlog,
            'sessionTwoBlog' => $sessionTwoBlog,
        ];
    }

    // -------------------------------------------------------------------------
    // Session-only blog context (#1777)
    // -------------------------------------------------------------------------

    public function testBaseCourseBlogCollectionExcludesSessionBlogs(): void
    {
        $ctx = $this->bootstrapBlogSessionScenario('base_collection');
        $token = $this->getUserTokenFromUser($ctx['baseStudent']);

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/c_blogs?cid='.$ctx['course']->getId(),
        );

        $this->assertResponseStatusCodeSame(200);
        $content = $response->getContent(false);
        $this->assertStringContainsString('Base Blog base_collection', $content);
        $this->assertStringNotContainsString('Session One Blog base_collection', $content);
        $this->assertStringNotContainsString('Session Two Blog base_collection', $content);
    }

    public function testSessionBlogCollectionDoesNotInheritBaseOrOtherSessionBlogs(): void
    {
        $ctx = $this->bootstrapBlogSessionScenario('session_collection');
        $token = $this->getUserTokenFromUser($ctx['sessionOneStudent']);

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/c_blogs?cid='.$ctx['course']->getId().'&sid='.$ctx['sessionOne']->getId(),
        );

        $this->assertResponseStatusCodeSame(200);
        $content = $response->getContent(false);
        $this->assertStringContainsString('Session One Blog session_collection', $content);
        $this->assertStringNotContainsString('Base Blog session_collection', $content);
        $this->assertStringNotContainsString('Session Two Blog session_collection', $content);
    }

    public function testBaseCourseBlogCannotBeOpenedFromSessionContext(): void
    {
        $ctx = $this->bootstrapBlogSessionScenario('session_item');
        $token = $this->getUserTokenFromUser($ctx['sessionOneStudent']);

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/c_blogs/'.$ctx['baseBlog']->getIid()
                .'?cid='.$ctx['course']->getId()
                .'&sid='.$ctx['sessionOne']->getId(),
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testSessionBlogCannotBeOpenedFromBaseCourseContext(): void
    {
        $ctx = $this->bootstrapBlogSessionScenario('base_item');
        $token = $this->getUserTokenFromUser($ctx['baseStudent']);

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/c_blogs/'.$ctx['sessionOneBlog']->getIid().'?cid='.$ctx['course']->getId(),
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testSessionCoachCannotCreatePostInBaseCourseBlog(): void
    {
        $ctx = $this->bootstrapBlogSessionScenario('session_write');
        $token = $this->getUserTokenFromUser($ctx['sessionOneCoach']);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_blog_posts?cid='.$ctx['course']->getId().'&sid='.$ctx['sessionOne']->getId(),
            [
                'json' => [
                    'title' => 'Session coach cross-context post',
                    'fullText' => 'This must not be persisted in the base-course blog.',
                    'blog' => '/api/c_blogs/'.$ctx['baseBlog']->getIid(),
                ],
            ]
        );

        // The context query extension hides the foreign blog IRI during
        // API Platform denormalization, so the crafted relation is rejected as
        // an invalid request before the processor can persist anything.
        $this->assertResponseStatusCodeSame(400);

        $count = $this->getEntityManager()->getRepository(CBlogPost::class)->count([
            'blog' => $ctx['baseBlog']->getIid(),
        ]);
        $this->assertSame(0, $count);
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
