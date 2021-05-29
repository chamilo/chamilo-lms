<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use DateTime;
use Symfony\Component\HttpFoundation\Request;

class UpdateDocumentFileAction extends BaseResourceFileAction
{
    public function __invoke(CDocument $document, Request $request, CDocumentRepository $repo): CDocument
    {
        error_log('UpdateDocumentFileAction __invoke');

        $this->handleUpdateRequest($document, $repo, $request);

        //$document->setComment($comment);

        error_log('Finish update resource node file action');

        return $document;
    }
}
