<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\Document\CourseDocumentCreator;
use Chamilo\CourseBundle\Entity\CDocument;
use Symfony\Component\HttpFoundation\Request;

/**
 * The HTTP face of CourseDocumentCreator.
 *
 * No course is handed over: CidReqListener resolved the context that gated this
 * operation, and the creator reads it from there. Callers that are not a request
 * go through CourseDocumentCreator::create() instead.
 */
final class CreateDocumentFileAction
{
    public function __invoke(Request $request, CourseDocumentCreator $creator): CDocument
    {
        return $creator->createFromRequest($request);
    }
}
