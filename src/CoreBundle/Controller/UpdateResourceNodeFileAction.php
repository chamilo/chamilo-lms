<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UpdateResourceNodeFileAction
{
    public function __invoke(Request $request, CDocumentRepository $repo): CDocument
    {
        error_log('__invoke update');

        $id = (int) $request->get('id');
        error_log($id);
        if (0 === $id) {
            throw new \InvalidArgumentException('Not valid id');
        }

        /** @var CDocument $document */
        $document = $repo->find($id);

        if (null === $document) {
            throw new \InvalidArgumentException("Resource $id not found");
        }

        error_log($request->getMethod());
        $fileType = $document->getFileType();

        $data = $request->getContent();
        $title = $request->get('title');
        error_log($title);
        $title = $request->request->get('title');

        error_log($title);

        $comment = $request->request->get('comment');

        error_log($comment);

        if (empty($data)) {
            throw new \InvalidArgumentException("No data");
        }


        error_log(print_r($data, 1));
        $data = json_decode($data, true);
        error_log(print_r($data, 1));

        $title = $data['title'];
        $content = $data['contentFile'];
        $comment = $data['comment'];
        //$nodeId = (int) $request->get('parentResourceNodeId');
        //$document->setParentResourceNode($nodeId);
        $document->setTitle($title);

        if ('file' === $fileType) {
            $repo->updateResourceFileContent($document, $content);
        }

        /*if ($request->request->has('resourceLinkList')) {
            $links = $request->get('resourceLinkList');
            if (false === strpos($links, '[')) {
                $links = json_decode('['.$links.']', true);
            } else {
                $links = json_decode($links, true);
            }
            if (empty($links)) {
                throw new \InvalidArgumentException(
                    'resourceLinkList is not a valid json. Example: [{"c_id":1:"visibility":1}]'
                );
            }
            $document->setResourceLinkList($links);
        }*/

        $repo->setResourceTitle($document, $title);

        //$document->setTitle($title);
        $document->setComment($comment);
        //$document->getResourceNode()->setTitle($title);

        return $document;
    }
}
