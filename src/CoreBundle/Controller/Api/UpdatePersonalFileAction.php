<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Chamilo\CoreBundle\Service\Storage\StorageQuotaService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UpdatePersonalFileAction extends BaseResourceFileAction
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly StorageQuotaService $storageQuotaService,
    ) {}

    public function __invoke(PersonalFile $resource, Request $request, PersonalFileRepository $repo, EntityManager $em): PersonalFile
    {
        if ('false' === $this->settingsManager->getSetting('platform.allow_my_files', true)) {
            throw new AccessDeniedHttpException('Personal files are disabled.');
        }

        $payload = json_decode((string) $request->getContent(), true);
        $content = \is_array($payload) && \array_key_exists('contentFile', $payload)
            ? (string) $payload['contentFile']
            : $request->request->get('contentFile');
        $owner = $resource->getResourceNode()?->getCreator();

        return $em->wrapInTransaction(function () use ($resource, $repo, $request, $em, $content, $owner): PersonalFile {
            if (null !== $owner && null !== $content) {
                $this->storageQuotaService->lockUser($owner);
                $originalSize = (int) ($resource->getResourceNode()?->getFirstResourceFile()?->getSize() ?? 0);
                $this->storageQuotaService->assertPersonalQuota($owner, \strlen((string) $content), $originalSize);
            }

            $this->handleUpdateRequest(
                $resource,
                $repo,
                $request,
                $em,
                fn (array $links) => $this->assertResourceLinksAreUserShares($links),
            );

            return $resource;
        });
    }
}
