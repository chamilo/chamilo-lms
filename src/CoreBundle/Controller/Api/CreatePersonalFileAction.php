<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Chamilo\CoreBundle\Service\Storage\StorageQuotaService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CreatePersonalFileAction extends BaseResourceFileAction
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly StorageQuotaService $storageQuotaService,
        private readonly UserHelper $userHelper,
    ) {}

    /**
     * @psalm-suppress InvalidArgument Psalm cannot bind the interface template T of wrapInTransaction() on the concrete EntityManager
     */
    public function __invoke(Request $request, PersonalFileRepository $repo, EntityManager $em, CidReqHelper $cidReqHelper): PersonalFile
    {
        if ('false' === $this->settingsManager->getSetting('platform.allow_my_files', true)) {
            throw new AccessDeniedHttpException('Personal files are disabled.');
        }

        $user = $this->userHelper->getCurrent();
        if (null === $user) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $payload = json_decode((string) $request->getContent(), true);
        $parentId = $this->storageQuotaService->normalizeParentNodeId(
            $request->request->get('parentResourceNodeId')
                ?? (\is_array($payload) ? ($payload['parentResourceNodeId'] ?? 0) : 0)
        );
        $this->storageQuotaService->assertPersonalParent($user, $parentId);

        $file = $request->files->get('uploadFile');
        $incomingBytes = $file instanceof UploadedFile
            ? (int) ($file->getSize() ?: 0)
            : ($request->request->has('contentFile')
                ? \strlen((string) $request->request->get('contentFile'))
                : (\is_array($payload) && isset($payload['contentFile']) ? \strlen((string) $payload['contentFile']) : 0));

        return $em->wrapInTransaction(function () use ($user, $incomingBytes, $repo, $request, $em, $cidReqHelper): PersonalFile {
            $this->storageQuotaService->lockUser($user);
            $this->storageQuotaService->assertPersonalQuota($user, $incomingBytes);

            $resource = new PersonalFile();
            // Personal files never receive course links from the client.
            $result = $this->handleCreateFileRequest($resource, $repo, $request, $em, $cidReqHelper, 'overwrite', resourceLinkListOverride: []);
            $resource->setTitle($result['title']);
            $resource->setCreator($user);
            $em->persist($resource);

            return $resource;
        });
    }
}
