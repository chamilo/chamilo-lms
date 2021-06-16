<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CDocument;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

class CreateDocumentFileAction extends BaseResourceFileAction
{
    public function __invoke(Request $request): CDocument
    {
        error_log('CreateDocumentFileAction __invoke');

        $document = new CDocument();
        $result = $this->handleCreateRequest($document, $request);

        $document->setFiletype($result['filetype']);
        $document->setComment($result['comment']);

        // Specific for the CDocument because it needs to be registered in a course.
        if (!empty($result['resourceLinkList'])) {
            $document->setResourceLinkArray($result['resourceLinkList']);
        }

        return $document;
    }
}
