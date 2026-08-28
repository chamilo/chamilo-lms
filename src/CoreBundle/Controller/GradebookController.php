<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\GradeBookCategoryRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/gradebook')]
class GradebookController extends AbstractController
{
    public function __construct(
        private readonly GradeBookCategoryRepository $gradeBookCategoryRepository,
    ) {}

    #[Route('/categories', name: 'chamilo_core_gradebook_categories', methods: ['GET'])]
    public function getCategories(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Extract parameters from the query string
        $courseId = (int) $request->query->get('courseId');
        $sessionId = $request->query->get('sessionId') ? (int) $request->query->get('sessionId') : null;

        if (!$courseId) {
            return new JsonResponse(['error' => 'courseId parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $course = $entityManager->getRepository(Course::class)->find($courseId);

        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);

        // Ensure the default category exists
        $this->gradeBookCategoryRepository->createDefaultCategory($courseId, $sessionId);

        // Fetch categories using the repository
        $categories = $this->gradeBookCategoryRepository->getCategoriesForCourse($courseId, $sessionId);

        // Format the response
        $formatted = array_map(fn ($category) => [
            'id' => $category->getId(),
            'title' => $category->getTitle(),
            'parentId' => $category->getParent()?->getId(),
        ], $categories);

        return new JsonResponse($formatted);
    }

}
