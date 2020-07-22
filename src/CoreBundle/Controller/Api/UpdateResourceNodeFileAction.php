<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class UpdateResourceNodeFileAction
{
    public function __invoke(CDocument $document, Request $request, CDocumentRepository $repo, EntityManagerInterface $em): CDocument
    {
        $fileType = $document->getFileType();
        $contentData = $request->getContent();
        error_log('__invoke');

        if (!empty($contentData)) {
            $contentData = json_decode($contentData, true);
            $title = $contentData['title'];
            $content = $contentData['contentFile'];
            $comment = $contentData['comment'];
        } else {
            $title = $request->get('title');
            $content = $request->request->get('contentFile');
            $comment = $request->request->get('comment');
        }
        //$comment = time();
        $document->setTitle($title);
        if ('file' === $fileType && !empty($content)) {
            $resourceNode = $document->getResourceNode();

            error_log('to update');
            //$document->setUploadFile($file);
            //$repo->updateResourceFileContent($document, $content);
            if ($resourceNode->hasResourceFile()) {
                $resourceNode->setContent($content);
                $resourceNode->getResourceFile()->setSize(strlen($content));
            }
            $resourceNode->setUpdatedAt(new \DateTime());
            $resourceNode->getResourceFile()->setUpdatedAt(new \DateTime());
            $document->setResourceNode($resourceNode);
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
        $document->setComment($comment);

        error_log('Finish update resource node file action');

        return $document;
    }
}
