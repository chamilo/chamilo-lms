<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class UpdatePersonalFileAction extends BaseResourceFileAction
{
    public function __invoke(PersonalFile $resource, Request $request, PersonalFileRepository $repo, EntityManager $em): PersonalFile
    {
        error_log('UpdatePersonalFileAction __invoke');

        $this->handleUpdateRequest($resource, $repo, $request, $em);

        //error_log('Finish update resource node file action');

        return $resource;
    }
}
