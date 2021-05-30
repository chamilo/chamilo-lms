<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Symfony\Component\HttpFoundation\Request;

class UpdatePersonalFileAction extends BaseResourceFileAction
{
    public function __invoke(PersonalFile $document, Request $request, CDocumentRepository $repo): PersonalFile
    {
        error_log('UpdatePersonalFileAction __invoke');

        $this->handleUpdateRequest($document, $repo, $request);

        error_log('Finish update resource node file action');

        return $document;
    }
}
