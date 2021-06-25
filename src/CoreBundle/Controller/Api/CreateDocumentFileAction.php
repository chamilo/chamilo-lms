<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class CreateDocumentFileAction extends BaseResourceFileAction
{
    public function __invoke(Request $request, EntityManager $em): CDocument
    {
        //error_log('CreateDocumentFileAction __invoke');
        $document = new CDocument();
        $result = $this->handleCreateRequest($document, $request, $em);

        $document->setFiletype($result['filetype']);
        $document->setComment($result['comment']);

        return $document;
    }
}
