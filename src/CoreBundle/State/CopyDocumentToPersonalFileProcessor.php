<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<CDocument, PersonalFile>
 */
final readonly class CopyDocumentToPersonalFileProcessor implements ProcessorInterface
{
    public function __construct(
        private SettingsManager $settingsManager,
        private UserHelper $userHelper,
        private ResourceNodeRepository $resourceNodeRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PersonalFile
    {
        if ('false' === $this->settingsManager->getSetting('platform.allow_my_files', true)) {
            throw new AccessDeniedHttpException('Personal files are disabled.');
        }

        if ('false' === $this->settingsManager->getSetting('document.users_copy_files', true)) {
            throw new AccessDeniedHttpException('Copy to personal files is disabled.');
        }

        $user = $this->userHelper->getCurrent();

        if (!$user) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        assert($data instanceof CDocument);

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

        $tmpPath = tempnam(sys_get_temp_dir(), 'doc_copy_');

        if (false === $tmpPath) {
            throw new BadRequestHttpException('Could not create a temporary file.');
        }

        try {
            $stream = $this->resourceNodeRepository->getResourceNodeFileStream($resourceNode, $resourceFile);
            $tmpHandle = fopen($tmpPath, 'wb');

            if (false === $tmpHandle) {
                throw new BadRequestHttpException('Could not open temporary file for writing.');
            }

            stream_copy_to_stream($stream, $tmpHandle);
            fclose($tmpHandle);
            fclose($stream);

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
