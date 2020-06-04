<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateResourceFileAction
{
    public function __invoke(Request $request): ResourceFile
    {
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }

        $resourceFile = new ResourceFile();
        $resourceFile->setName($uploadedFile->getFilename());
        $resourceFile->setFile($uploadedFile);

        return $resourceFile;
    }
}
