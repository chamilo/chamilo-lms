<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The session admin "Send course invitation" button creates one session per learner through
 * POST /api/advanced/create-session-with-courses-and-users with copyEvaluation=true, so the
 * learner is graded with the base course's gradebook. A copied subcategory must hang from
 * the session's copy of its parent: left attached to the base course's category, it would be
 * counted in the base course's gradebook and missing from the session's one.
 */
class CreateSessionWithUsersAndCoursesActionTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCopiedSubcategoryHangsFromTheSessionCopyOfItsParent(): void
    {
        $course = $this->createCourse('Gradebook copy course');
        $root = $this->createBaseCategory($course, 'Root category', null);
        $this->createBaseCategory($course, 'Child category', $root);

        $response = $this->createClientWithCredentials($this->getUserToken())->request(
            'POST',
            '/api/advanced/create-session-with-courses-and-users',
            [
                'json' => [
                    'title' => 'Gradebook copy session '.uniqid(),
                    'copyEvaluation' => true,
                    'courseIds' => [$course->getId()],
                    'studentIds' => [],
                    'tutorIds' => [],
                ],
            ]
        );
        $this->assertResponseIsSuccessful();
        $sessionId = $response->toArray()['id'];

        $em = $this->getEntityManager();
        $em->clear();
        $repo = $em->getRepository(GradebookCategory::class);

        $sessionRoot = $repo->findOneBy(['course' => $course->getId(), 'session' => $sessionId, 'title' => 'Root category']);
        $sessionChild = $repo->findOneBy(['course' => $course->getId(), 'session' => $sessionId, 'title' => 'Child category']);

        $this->assertNotNull($sessionRoot);
        $this->assertNotNull($sessionChild);
        $this->assertNull($sessionRoot->getParent());
        $this->assertSame($sessionRoot->getId(), $sessionChild->getParent()?->getId());
    }

    private function createBaseCategory(Course $course, string $title, ?GradebookCategory $parent): GradebookCategory
    {
        $category = (new GradebookCategory())
            ->setTitle($title)
            ->setWeight(100)
            ->setVisible(true)
            ->setUser($this->getAdmin())
            ->setCourse($course)
            ->setParent($parent)
        ;

        $em = $this->getEntityManager();
        $em->persist($category);
        $em->flush();

        return $category;
    }
}
