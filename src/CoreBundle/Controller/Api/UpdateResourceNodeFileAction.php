<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Symfony\Component\HttpFoundation\Request;

class UpdateResourceNodeFileAction
{
    public function __invoke(CDocument $document, Request $request, CDocumentRepository $repo): CDocument
    {
        $fileType = $document->getFileType();
        $contentData = $request->getContent();
        error_log('UpdateResourceNodeFileAction __invoke');
        $resourceLinkList = [];
        if (!empty($contentData)) {
            error_log('contentData');
            $contentData = json_decode($contentData, true);
            $title = $contentData['title'];
            $content = $contentData['contentFile'];
            $comment = $contentData['comment'] ?? '';
            $resourceLinkList = $contentData['resourceLinkList'] ?? [];
        } else {
            $title = $request->get('title');
            $content = $request->request->get('contentFile');
            $comment = $request->request->get('comment');
        }

        $repo->setResourceName($document, $title);

        if ('file' === $fileType && !empty($content)) {
            $resourceNode = $document->getResourceNode();
            if ($resourceNode->hasResourceFile()) {
                $resourceNode->setContent($content);
                $resourceNode->getResourceFile()->setSize(strlen($content));
            }
            $resourceNode->setUpdatedAt(new \DateTime());
            $resourceNode->getResourceFile()->setUpdatedAt(new \DateTime());
            $document->setResourceNode($resourceNode);
        }

        if (!empty($resourceLinkList)) {
            foreach ($resourceLinkList as $linkArray) {
                $linkId = $linkArray['id'];
                /** @var ResourceLink $link */
                $link = $document->getResourceNode()->getResourceLinks()
                    ->filter(
                        function ($link) use ($linkId) {
                            return $link->getId() === $linkId;
                        }
                    )->first();

                if (null !== $link) {
                    $link->setVisibility((int) $linkArray['visibility']);
                }
            }
        }

        $document->setComment($comment);

        error_log('Finish update resource node file action');

        return $document;
    }
}
