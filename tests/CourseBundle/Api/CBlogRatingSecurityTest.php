<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CBlog;
use Chamilo\CourseBundle\Entity\CBlogPost;
use Chamilo\CourseBundle\Entity\CBlogRating;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * Regression tests for the blog rating advisory.
 *
 * The rating API trusted the client for the author and for the blog and post it points at, so a
 * student could rate in another course, rate as another user, and change or delete ratings that
 * were not theirs.
 */
final class CBlogRatingSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * Builds a private course with a blog and a post, and two students of that course.
     *
     * @return array{course: Course, student: User, classmate: User, blog: CBlog, post: CBlogPost}
     */
    private function bootstrapCourse(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Rating Sec Course '.$suffix);
        $course->setVisibility(Course::REGISTERED);

        $teacher = $this->createUser('rating_sec_teacher_'.$suffix);
        $student = $this->createUser('rating_sec_student_'.$suffix);
        $classmate = $this->createUser('rating_sec_classmate_'.$suffix);
        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $course->addUserAsStudent($classmate);
        $courseRepo->update($course);

        $em = $this->getEntityManager();
        $blog = (new CBlog())
            ->setTitle('Rating Blog '.$suffix)
            ->setBlogSubtitle('subtitle')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($blog);
        $em->flush();

        $post = (new CBlogPost())
            ->setTitle('Rating Post '.$suffix)
            ->setFullText('Post body')
            ->setBlog($blog)
            ->setAuthor($teacher)
        ;
        $em->persist($post);
        $em->flush();

        return ['course' => $course, 'student' => $student, 'classmate' => $classmate, 'blog' => $blog, 'post' => $post];
    }

    private function createRating(CBlog $blog, CBlogPost $post, User $user, int $score = 3): CBlogRating
    {
        $rating = (new CBlogRating())
            ->setBlog($blog)
            ->setPost($post)
            ->setUser($user)
            ->setRating($score)
        ;
        $em = $this->getEntityManager();
        $em->persist($rating);
        $em->flush();

        return $rating;
    }

    /**
     * @param array<string, mixed> $json
     */
    private function request(User $user, string $method, string $uri, array $json = []): void
    {
        $options = [];
        if ([] !== $json) {
            $options['json'] = $json;
            $options['headers'] = ['Content-Type' => 'PATCH' === $method ? 'application/merge-patch+json' : 'application/ld+json'];
        }

        $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request($method, $uri, $options);
    }

    private function ratingPayload(CBlog $blog, CBlogPost $post, int $score = 5): array
    {
        return [
            'ratingType' => 'post',
            'rating' => $score,
            'blog' => '/api/c_blogs/'.$blog->getIid(),
            'post' => '/api/c_blog_posts/'.$post->getIid(),
        ];
    }

    private function freshRating(CBlogRating $rating): ?CBlogRating
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->getRepository(CBlogRating::class)->find($rating->getIid());
    }

    public function testStudentRatingIsAttributedToTheAuthenticatedUserEvenIfAnotherOneIsSent(): void
    {
        $ctx = $this->bootstrapCourse('impersonate');

        $this->request(
            $ctx['student'],
            'POST',
            '/api/c_blog_ratings?cid='.$ctx['course']->getId(),
            $this->ratingPayload($ctx['blog'], $ctx['post']) + ['user' => '/api/users/'.$ctx['classmate']->getId()]
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['user' => '/api/users/'.$ctx['student']->getId()]);
    }

    public function testStudentCannotRateInAnotherCourseUsingOwnCid(): void
    {
        $own = $this->bootstrapCourse('foreign_own');
        $other = $this->bootstrapCourse('foreign_other');
        $countBefore = $this->getEntityManager()->getRepository(CBlogRating::class)->count([]);

        $this->request(
            $own['student'],
            'POST',
            '/api/c_blog_ratings?cid='.$own['course']->getId(),
            $this->ratingPayload($other['blog'], $other['post'])
        );

        // Refused either while the foreign IRIs are resolved (400) or by the security check (403).
        $this->assertContains($this->getResponseStatusCode(), [400, 403]);
        $this->assertSame($countBefore, $this->getEntityManager()->getRepository(CBlogRating::class)->count([]));
    }

    public function testRatingCannotPointAtAPostOfAnotherBlogOfTheSameCourse(): void
    {
        $ctx = $this->bootstrapCourse('mismatch');

        $em = $this->getEntityManager();
        $otherBlog = (new CBlog())
            ->setTitle('Rating Blog mismatch 2')
            ->setBlogSubtitle('subtitle')
            ->setParent($ctx['course'])
            ->setCreator($ctx['student'])
            ->addCourseLink($ctx['course'])
        ;
        $em->persist($otherBlog);
        $em->flush();

        $this->request(
            $ctx['student'],
            'POST',
            '/api/c_blog_ratings?cid='.$ctx['course']->getId(),
            $this->ratingPayload($otherBlog, $ctx['post'])
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testStudentCanChangeTheirOwnRatingButNotWhereItPoints(): void
    {
        $own = $this->bootstrapCourse('patch_own');
        $other = $this->bootstrapCourse('patch_own_other');
        $rating = $this->createRating($own['blog'], $own['post'], $own['student'], 2);

        $this->request(
            $own['student'],
            'PATCH',
            '/api/c_blog_ratings/'.$rating->getIid().'?cid='.$own['course']->getId(),
            ['rating' => 4, 'blog' => '/api/c_blogs/'.$other['blog']->getIid(), 'post' => '/api/c_blog_posts/'.$other['post']->getIid()]
        );

        $this->assertResponseIsSuccessful();
        $fresh = $this->freshRating($rating);
        $this->assertSame(4, $fresh?->getRating());
        $this->assertSame($own['blog']->getIid(), $fresh?->getBlog()?->getIid());
        $this->assertSame($own['post']->getIid(), $fresh?->getPost()?->getIid());
    }

    public function testStudentCannotChangeARatingOfAClassmate(): void
    {
        $ctx = $this->bootstrapCourse('patch_classmate');
        $rating = $this->createRating($ctx['blog'], $ctx['post'], $ctx['classmate'], 2);

        $this->request(
            $ctx['student'],
            'PATCH',
            '/api/c_blog_ratings/'.$rating->getIid().'?cid='.$ctx['course']->getId(),
            ['rating' => 5]
        );

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(2, $this->freshRating($rating)?->getRating());
    }

    public function testStudentCannotDeleteARatingOfAClassmate(): void
    {
        $ctx = $this->bootstrapCourse('delete_classmate');
        $rating = $this->createRating($ctx['blog'], $ctx['post'], $ctx['classmate']);

        $this->request($ctx['student'], 'DELETE', '/api/c_blog_ratings/'.$rating->getIid().'?cid='.$ctx['course']->getId());

        $this->assertResponseStatusCodeSame(403);
        $this->assertNotNull($this->freshRating($rating));
    }

    public function testStudentCannotTouchARatingOfAnotherCourseUsingOwnCid(): void
    {
        $own = $this->bootstrapCourse('foreign_item_own');
        $other = $this->bootstrapCourse('foreign_item_other');
        $rating = $this->createRating($other['blog'], $other['post'], $other['student'], 2);

        $this->request($own['student'], 'PATCH', '/api/c_blog_ratings/'.$rating->getIid().'?cid='.$own['course']->getId(), ['rating' => 5]);
        $this->assertContains($this->getResponseStatusCode(), [403, 404]);

        $this->request($own['student'], 'DELETE', '/api/c_blog_ratings/'.$rating->getIid().'?cid='.$own['course']->getId());
        $this->assertContains($this->getResponseStatusCode(), [403, 404]);

        $fresh = $this->freshRating($rating);
        $this->assertNotNull($fresh);
        $this->assertSame(2, $fresh->getRating());
    }

    private function getResponseStatusCode(): int
    {
        return self::getClient()->getResponse()->getStatusCode();
    }
}
