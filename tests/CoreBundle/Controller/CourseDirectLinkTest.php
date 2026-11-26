<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CourseDirectLinkTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testEnrolledLearnerIsRedirectedToBaseCourse(): void
    {
        $client = self::createClient();
        $course = $this->createCourse('Direct link enrolled learner');
        $course->setVisibility(Course::REGISTERED);
        $student = $this->createUser('direct_link_student');
        $course->addUserAsStudent($student);
        self::getContainer()->get(CourseRepository::class)->update($course);
        $client->loginUser($student);

        $client->request('GET', '/course/'.$course->getId());
        $this->assertResponseRedirects('/course/'.$course->getId().'/home?sid=0');
    }

    public function testUnrelatedLearnerCannotUsePrivateCourseDirectLink(): void
    {
        $client = self::createClient();
        $course = $this->createCourse('Private direct link');
        $course->setVisibility(Course::REGISTERED);
        self::getContainer()->get(CourseRepository::class)->update($course);
        $client->loginUser($this->createUser('direct_link_outsider'));

        $client->request('GET', '/course/'.$course->getId());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testMissingCourseReturnsNotFound(): void
    {
        $client = self::createClient();
        $client->loginUser($this->getUser('admin'));

        $client->request('GET', '/course/2147483647');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testExistingCourseRoutesAreNotShadowed(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get('router');

        self::assertSame('chamilo_core_course_direct', $router->match('/course/13')['_route']);
        self::assertSame('chamilo_core_course_home', $router->match('/course/13/home')['_route']);
        self::assertSame('chamilo_core_course_about', $router->match('/course/13/about')['_route']);
        self::assertSame('chamilo_core_course_search_templates', $router->match('/course/search_templates')['_route']);
    }
}
