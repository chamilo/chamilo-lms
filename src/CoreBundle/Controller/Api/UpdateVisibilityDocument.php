<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\ServiceHelper\CidReqHelper;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UpdateVisibilityDocument extends AbstractController
{
    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
    ) {}

    public function __invoke(CDocument $document, CDocumentRepository $repo): CDocument
    {
        $repo->toggleVisibilityPublishedDraft(
            $document,
            $this->cidReqHelper->getCourseEntity(),
            $this->cidReqHelper->getSessionEntity()
        );

        return $document;
    }
}
