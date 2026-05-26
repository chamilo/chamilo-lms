<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CreatePersonalFileAction extends BaseResourceFileAction
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
    ) {}

    public function __invoke(Request $request, PersonalFileRepository $repo, EntityManager $em): PersonalFile
    {
        if ('false' === $this->settingsManager->getSetting('platform.allow_my_files', true)) {
            throw new AccessDeniedHttpException('Personal files are disabled.');
        }

        $resource = new PersonalFile();
        $result = $this->handleCreateFileRequest($resource, $repo, $request, $em, 'overwrite');

        $resource->setTitle($result['title']);

        return $resource;
    }
}
