<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\PersonalFile;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class CreatePersonalFileAction extends BaseResourceFileAction
{
    public function __invoke(Request $request, EntityManager $em): PersonalFile
    {
        $resource = new PersonalFile();
        $this->handleCreateRequest($resource, $request, $em);

        return $resource;
    }
}
