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
        $this->handleCreateRequest($document, $request);
        if ($request->request->has('filetype')) {
            $document->setFiletype($request->get('filetype'));
        }

        if ($request->request->has('resourceLinkList')) {
            $links = $request->get('resourceLinkList');
            $links = false === strpos($links, '[') ? json_decode('['.$links.']', true) : json_decode($links, true);
            if (empty($links)) {
                $message = 'resourceLinkList is not a valid json. Use for example: [{"c_id":1, "visibility":1}]';

                throw new InvalidArgumentException($message);
            }
            $document->setResourceLinkArray($links);
        }

        //$document->setComment($comment);

        return $document;
    }
}
