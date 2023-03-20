<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class CreateDocumentFileAction extends BaseResourceFileAction
{
    public function __invoke(Request $request, CDocumentRepository $repo, EntityManager $em): CDocument
    {
        $document = new CDocument();
        $result = $this->handleCreateFileRequest($document, $repo, $request);

        $document->setFiletype($result['filetype']);
        $document->setComment($result['comment']);

        return $document;
    }
}
