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
        $document = new CDocument();
        $title = $request->get('title');

        if ('file' === $request->get('filetype') && $request->files->count() > 0) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('uploadFile');
            if (!$uploadedFile) {
                throw new BadRequestHttpException('"uploadFile" is required');
            }
            $title = $uploadedFile->getClientOriginalName();
            $document->setUploadFile($uploadedFile);
        }

        if ($request->request->has('resourceLinks')) {
            //$links = json_decode('['.$request->get('resourceLinks').']', true);
            $links = json_decode($request->get('resourceLinks'), true);
            if (empty($links)) {
                throw new \InvalidArgumentException('sharingLinks is not a json');
            }
            $document->setResourceLinkList($links);
        }

        $document->setTitle($title);
        $document->setComment($request->get('comment'));

        $nodeId = (int) $request->get('parentResourceNodeId');
        $document->setParentResourceNode($nodeId);

        return $document;
    }
}
