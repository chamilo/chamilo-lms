<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UpdateVisibilityDocument extends AbstractController
{
    public function __invoke(CDocument $document, CDocumentRepository $repo): CDocument
    {
        $repo->toggleVisibilityPublishedDraft($document);

        return $document;
    }
}
