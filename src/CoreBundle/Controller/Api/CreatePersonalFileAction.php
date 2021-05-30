<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\PersonalFile;
use Symfony\Component\HttpFoundation\Request;

class CreatePersonalFileAction extends BaseResourceFileAction
{
    public function __invoke(Request $request): PersonalFile
    {
        error_log('CreatePersonalFileAction __invoke');

        $document = new PersonalFile();
        $this->handleCreateRequest($document, $request);

        return $document;
    }
}
