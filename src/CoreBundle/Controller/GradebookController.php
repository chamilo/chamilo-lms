<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Repository\GradeBookCategoryRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/gradebook')]
class GradebookController extends AbstractController
{
    public function __construct(
        private readonly GradeBookCategoryRepository $gradeBookCategoryRepository,
    ) {}

    #[Route('/categories', name: 'chamilo_core_gradebook_categories', methods: ['GET'])]
    public function getCategories(Request $request): JsonResponse
    {
        // Extract parameters from the query string
        $courseId = (int) $request->query->get('courseId');
        $sessionId = $request->query->get('sessionId') ? (int) $request->query->get('sessionId') : null;

        if (!$courseId) {
            return new JsonResponse(['error' => 'courseId parameter is required'], Response::HTTP_BAD_REQUEST);
        }

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

    // Sets the default certificate for a gradebook category
    #[Route('/set_default_certificate/{cid}/{certificateId}', name: 'chamilo_core_gradebook_set_default_certificate')]
    public function setDefaultCertificate(int $cid, int $certificateId, EntityManagerInterface $entityManager): Response
    {
        // Find the gradebook category by course ID
        $gradebookCategory = $entityManager->getRepository(GradebookCategory::class)->findOneBy(['course' => $cid]);

        // Check if the category and certificate exist
        if (!$gradebookCategory) {
            return new Response('Gradebook category not found', Response::HTTP_NOT_FOUND);
        }

        $certificate = $entityManager->getRepository(CDocument::class)->find($certificateId);
        if (!$certificate) {
            return new Response('Certificate not found', Response::HTTP_NOT_FOUND);
        }

        // Set the certificate as default for the gradebook category
        $gradebookCategory->setDocument($certificate);
        $entityManager->flush();

        // Return success response
        return new JsonResponse([
            'message' => 'Default certificate set successfully',
            'certificateId' => $certificate->getIid(),
            'gradebookCategoryId' => $gradebookCategory->getId(),
        ]);
    }

    // Gets the default certificate for a gradebook category
    #[Route('/default_certificate/{cid}', name: 'chamilo_core_gradebook_default_certificate')]
    public function getDefaultCertificate(int $cid, EntityManagerInterface $entityManager): JsonResponse
    {
        // Find the gradebook category by course ID
        $gradebookCategory = $entityManager->getRepository(GradebookCategory::class)->findOneBy(['course' => $cid]);

        // Check if the gradebook category exists for the course
        if (!$gradebookCategory) {
            return new JsonResponse(['message' => 'Gradebook category not found for the course', 'certificateId' => null], Response::HTTP_NOT_FOUND);
        }

        // Get the default certificate if it exists
        $defaultCertificate = $gradebookCategory->getDocument();

        if (!$defaultCertificate) {
            return new JsonResponse(['message' => 'No default certificate set', 'certificateId' => null], Response::HTTP_OK);
        }

        // Return success response with the default certificate ID
        return new JsonResponse([
            'message' => 'Default certificate found',
            'certificateId' => $defaultCertificate->getIid(),
        ]);
    }
}
