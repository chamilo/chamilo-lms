<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CourseBundle\Entity\CDocument;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateResourceNodeFileAction
{
    public function __invoke(Request $request): CDocument
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('resourceFile');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"resourceFile" is required');
        }
        $document = new CDocument();
        $document->setTitle($request->get('title'));
        $document->setComment($request->get('comment'));
        $nodeId = (int) str_replace('/api/resource_nodes/','',$request->get('parentResourceNode'));
        $document->setParentResourceNode($nodeId);
        $document->setResourceFile($uploadedFile);

        return $document;
    }
}
