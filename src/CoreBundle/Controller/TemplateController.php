<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Templates;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SystemTemplateRepository;
use Chamilo\CoreBundle\Repository\TemplatesRepository;
use Chamilo\CoreBundle\ServiceHelper\UserHelper;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/template')]
class TemplateController extends AbstractController
{
    #[Route('/document-templates/create', methods: ['POST'])]
    public function createDocumentTemplate(Request $request, EntityManagerInterface $entityManager, UserHelper $userHelper): Response
    {
        $documentId = (int) $request->request->get('refDoc');
        $title = $request->request->get('title');
        $cid = $request->request->get('cid');
        $imageFile = $request->files->get('thumbnail');

        if (!$imageFile) {
            return $this->json(['error' => 'No image provided.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userHelper->getCurrent();
        $course = null;
        if ($cid) {
            $course = $entityManager->getRepository(Course::class)->find($cid);
        }

        $asset = new Asset();
        $asset->setCategory(Asset::TEMPLATE);
        $asset->setFile($imageFile);
        $asset->setTitle($imageFile->getClientOriginalName());
        $entityManager->persist($asset);
        $entityManager->flush();

        $template = new Templates();
        $template->setTitle($title);
        $template->setDescription('');
        $template->setRefDoc($documentId);
        $template->setCourse($course);
        $template->setUser($user);
        $template->setImage($asset);
        $entityManager->persist($template);

        $document = $entityManager->getRepository(CDocument::class)->find($documentId);
        if ($document) {
            $document->setTemplate(true);
            $entityManager->persist($document);
        } else {
            return $this->json(['error' => 'Document not found.'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Template created successfully.']);
    }

    #[Route('/document-templates/{documentId}/is-template', methods: ['GET'])]
    public function isDocumentTemplate(int $documentId, EntityManagerInterface $entityManager): Response
    {
        $template = $entityManager->getRepository(Templates::class)->findOneBy(['refDoc' => $documentId]);

        return $this->json([
            'isTemplate' => null !== $template,
        ]);
    }

    #[Route('/document-templates/{documentId}/delete', methods: ['POST'])]
    public function deleteDocumentTemplate(int $documentId, EntityManagerInterface $entityManager): Response
    {
        $template = $entityManager->getRepository(Templates::class)->findOneBy(['refDoc' => $documentId]);

        if (!$template) {
            return $this->json(['error' => 'Template not found.'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($template);

        $document = $entityManager->getRepository(CDocument::class)->find($documentId);
        if ($document) {
            $document->setTemplate(false);
            $entityManager->persist($document);
        } else {
            return $this->json(['error' => 'Document not found.'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Template deleted successfully']);
    }

    #[Route('/all-templates/{courseId}', name: 'all-templates')]
    public function getAllTemplates($courseId, SystemTemplateRepository $systemTemplateRepository, TemplatesRepository $templatesRepository, CourseRepository $courseRepository, AssetRepository $assetRepository, CDocumentRepository $documentRepository): JsonResponse
    {
        $course = $courseRepository->find($courseId);
        if (!$course) {
            throw new NotFoundHttpException('Course not found');
        }

        $systemTemplates = $systemTemplateRepository->findAll();
        $platformTemplates = $this->formatSystemTemplates($systemTemplates, $assetRepository);

        $courseDocumentTemplates = $this->formatCourseDocumentTemplates($course, $templatesRepository, $assetRepository, $documentRepository);

        $allTemplates = array_merge($platformTemplates, $courseDocumentTemplates);

        return $this->json($allTemplates);
    }

    private function formatSystemTemplates(array $systemTemplates, AssetRepository $assetRepository): array
    {
        return array_map(function ($template) use ($assetRepository) {
            $imageUrl = null;
            if ($template->hasImage()) {
                $imageUrl = $assetRepository->getAssetUrl($template->getImage());
            }

            $content = $template->getContent();
            $content = str_replace('<table', '<table class="responsive-table"', $content);
            $content = str_replace(
                '{CSS}',
                '<style>
                .responsive-table {
                    width: 100%;
                    max-width: 100%;
                    overflow-x: auto;
                    display: block;
                    border-collapse: collapse;
                }
                .responsive-table th,
                .responsive-table td {
                    padding: 8px;
                    text-align: left;
                    word-wrap: break-word;
                    border: 1px solid #ccc;
                }
            </style>',
                $content
            );

            return [
                'id' => $template->getId(),
                'title' => $template->getTitle(),
                'comment' => $template->getComment(),
                'content' => $content,
                'image' => $imageUrl,
            ];
        }, $systemTemplates);
    }

    private function formatCourseDocumentTemplates(Course $course, TemplatesRepository $templatesRepository, AssetRepository $assetRepository, CDocumentRepository $documentRepository): array
    {
        $courseTemplates = $templatesRepository->findCourseDocumentTemplates($course);

        return array_map(function ($template) use ($assetRepository, $documentRepository) {
            $imageUrl = null;
            if ($template->hasImage()) {
                $imageUrl = $assetRepository->getAssetUrl($template->getImage());
            }

            $document = $documentRepository->find($template->getRefDoc());
            $content = '';
            if (null !== $document && null !== $document->getResourceNode() && $document->getResourceNode()->getResourceFiles()->first()) {
                $content = $documentRepository->getResourceFileContent($document);
            }

            return [
                'id' => $template->getId(),
                'title' => $template->getTitle(),
                'comment' => $template->getDescription(),
                'content' => $content,
                'image' => $imageUrl,
            ];
        }, $courseTemplates);
    }
}
