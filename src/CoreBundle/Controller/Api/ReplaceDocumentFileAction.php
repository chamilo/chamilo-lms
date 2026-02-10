<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;

class ReplaceDocumentFileAction extends BaseResourceFileAction
{
    private string $uploadBasePath;

    public function __construct(KernelInterface $kernel)
    {
        $this->uploadBasePath = $kernel->getProjectDir().'/var/upload/resource';
    }

    public function __invoke(
        CDocument $document,
        Request $request,
        ResourceNodeRepository $resourceNodeRepository,
        EntityManagerInterface $em,
        CourseRepository $courseRepository,
        CDocumentRepository $documentRepository,
        CourseHelper $courseHelper
    ): Response {
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required.');
        }

        $resourceNode = $document->getResourceNode();
        if (!$resourceNode) {
            throw new BadRequestHttpException('ResourceNode not found.');
        }

        $resourceFile = $resourceNode->getFirstResourceFile();
        if (!$resourceFile) {
            throw new BadRequestHttpException('No file found in the resource node.');
        }

        // Quota check BEFORE moving the file
        $oldBytes = (int) ($resourceFile->getSize() ?? 0);
        $newBytes = (int) ($uploadedFile->getSize() ?? 0);
        $deltaBytes = $newBytes - $oldBytes;

        if ($deltaBytes > 0) {
            $courses = [];
            foreach ($resourceNode->getResourceLinks() as $rl) {
                if ($rl instanceof ResourceLink && null !== $rl->getCourse()) {
                    $course = $rl->getCourse();
                    $courses[(int) $course->getId()] = $course;
                }
            }

            foreach ($courses as $course) {
                try {
                    $courseHelper->assertCanStoreDocumentBytes($course, $deltaBytes);
                } catch (\Throwable $e) {
                    throw new BadRequestHttpException(\sprintf(
                        'Not enough space in course #%d.',
                        (int) $course->getId()
                    ));
                }
            }
        }

        $rel = (string) $resourceNodeRepository->getFilename($resourceFile);
        if ('' === $rel) {
            throw new BadRequestHttpException('File path could not be resolved.');
        }
        $filePath = $this->uploadBasePath.$rel;

        $this->prepareDirectory($filePath);

        try {
            $uploadedFile->move(\dirname($filePath), basename($filePath));
        } catch (FileException $e) {
            throw new BadRequestHttpException(\sprintf('Failed to move the file: %s', $e->getMessage()));
        }

        if (!file_exists($filePath)) {
            throw new RuntimeException('The moved file does not exist at the expected location.');
        }

        $fileSize = (int) filesize($filePath);
        $resourceFile->setSize($fileSize);
        $newFileName = (string) $uploadedFile->getClientOriginalName();
        $document->setTitle($newFileName);
        $resourceNode->setTitle($newFileName);

        $resourceFile->setOriginalName($newFileName);
        $resourceNode->setUpdatedAt(new DateTime());

        $em->persist($document);
        $em->persist($resourceNode);
        $em->persist($resourceFile);
        $em->flush();

        return new Response('Document replaced successfully.', Response::HTTP_OK);
    }

    /**
     * Prepares the directory to ensure it exists and is writable.
     */
    protected function prepareDirectory(string $filePath): void
    {
        $directory = \dirname($filePath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException(\sprintf('Unable to create directory "%s".', $directory));
            }
        }

        if (!is_writable($directory)) {
            throw new RuntimeException(\sprintf('Directory "%s" is not writable.', $directory));
        }
    }
}
