<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
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
        EntityManagerInterface $em
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

        $filePath = $this->uploadBasePath.$resourceNodeRepository->getFilename($resourceFile);
        if (!$filePath) {
            throw new BadRequestHttpException('File path could not be resolved.');
        }

        $this->prepareDirectory($filePath);

        try {
            $uploadedFile->move(\dirname($filePath), basename($filePath));
        } catch (FileException $e) {
            throw new BadRequestHttpException(\sprintf('Failed to move the file: %s', $e->getMessage()));
        }

        if (!file_exists($filePath)) {
            throw new RuntimeException('The moved file does not exist at the expected location.');
        }

        $fileSize = filesize($filePath);
        $resourceFile->setSize($fileSize);

        $newFileName = $uploadedFile->getClientOriginalName();

        // Keep titles consistent: entity title + node title.
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
