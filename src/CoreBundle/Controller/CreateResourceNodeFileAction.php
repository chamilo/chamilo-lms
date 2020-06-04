<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CourseBundle\Entity\CDocument;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateResourceNodeFileAction
{
    public function __invoke(Request $request): CDocument
    {
        $uploadedFile = null;
        /** @var UploadedFile $uploadedFile */
        if ($request->files->count() > 0) {
            $uploadedFile = $request->files->get('resourceFile');
            if (!$uploadedFile) {
                throw new BadRequestHttpException('"resourceFile" is required');
            }
        }
        $document = new CDocument();
        $title = $request->get('title');
        if (empty($title) && $uploadedFile) {
            $title = $uploadedFile->getClientOriginalName();
        }
        $document->setTitle($title);
        $document->setComment($request->get('comment'));
        $nodeId = (int) str_replace('/api/resource_nodes/', '', $request->get('parentResourceNode'));
        $document->setParentResourceNode($nodeId);
        if ($uploadedFile) {
            $document->setResourceFile($uploadedFile);
        }

        return $document;
    }
}
