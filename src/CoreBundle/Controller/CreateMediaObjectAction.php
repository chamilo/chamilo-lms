<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Resource\ResourceFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateMediaObjectAction
{
    public function __invoke(Request $request): ResourceFile
    {
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }

        $resourceFile = new ResourceFile();
        $resourceFile->setFile($uploadedFile);

        return $resourceFile;
    }
}
