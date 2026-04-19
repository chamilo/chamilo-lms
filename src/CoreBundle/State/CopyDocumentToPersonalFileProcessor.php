<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @implements ProcessorInterface<CDocument, PersonalFile>
 */
final class CopyDocumentToPersonalFileProcessor implements ProcessorInterface
{
    private string $uploadBasePath;

    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly Security $security,
        private readonly ResourceNodeRepository $resourceNodeRepository,
        private readonly EntityManagerInterface $entityManager,
        KernelInterface $kernel,
    ) {
        $this->uploadBasePath = $kernel->getProjectDir().'/var/upload/resource';
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PersonalFile
    {
        if ('false' === $this->settingsManager->getSetting('platform.allow_my_files', true)) {
            throw new AccessDeniedHttpException('Personal files are disabled.');
        }

        if ('false' === $this->settingsManager->getSetting('document.users_copy_files', true)) {
            throw new AccessDeniedHttpException('Copy to personal files is disabled.');
        }

        $user = $this->security->getUser();

        if (!$user || !method_exists($user, 'getResourceNode')) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        \assert($data instanceof CDocument);

        if ('file' !== $data->getFiletype()) {
            throw new BadRequestHttpException('Only files can be copied to personal files.');
        }

        $resourceNode = $data->getResourceNode();

        if (null === $resourceNode) {
            throw new BadRequestHttpException('ResourceNode not found.');
        }

        $resourceFile = $resourceNode->getFirstResourceFile();

        if (null === $resourceFile) {
            throw new BadRequestHttpException('No file found in the resource node.');
        }

        $rel = (string) $this->resourceNodeRepository->getFilename($resourceFile);

        if ('' === $rel) {
            throw new BadRequestHttpException('File path could not be resolved.');
        }

        $filePath = $this->uploadBasePath.$rel;

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new BadRequestHttpException('Document source file was not found in storage: '.$filePath);
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'doc_copy_');

        if (false === $tmpPath) {
            throw new BadRequestHttpException('Could not create a temporary file.');
        }

        if (!@copy($filePath, $tmpPath)) {
            @unlink($tmpPath);

            throw new BadRequestHttpException('Could not copy the source document.');
        }

        $originalName = $resourceFile->getOriginalName() ?: $data->getTitle();

        $mimeType = $resourceFile->getMimeType();

        if (null === $mimeType || '' === $mimeType) {
            $detectedMimeType = mime_content_type($tmpPath);
            $mimeType = false !== $detectedMimeType ? $detectedMimeType : 'application/octet-stream';
        }

        $uploadedFile = new UploadedFile(
            $tmpPath,
            $originalName,
            $mimeType,
            null,
            true,
        );

        try {
            $parentNodeId = (int) $user->getResourceNode()->getId();

            $personalFile = new PersonalFile();
            $personalFile->setCreator($user);
            $personalFile->setParentResourceNode($parentNodeId);
            $personalFile->setResourceName($originalName);
            $personalFile->setComment('');
            $personalFile->setUploadFile($uploadedFile);

            $this->entityManager->persist($personalFile);
            $this->entityManager->flush();

            return $personalFile;
        } finally {
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }
}
