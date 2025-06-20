<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Repository\TrackEDefaultRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class UpdateDocumentFileAction extends BaseResourceFileAction
{
    public function __construct(
        private TrackEDefaultRepository $trackRepo,
        private Security $security
    ) {}

    public function __invoke(CDocument $document, Request $request, CDocumentRepository $repo, EntityManager $em): CDocument
    {
        $this->handleUpdateRequest($document, $repo, $request, $em);

        $node = $document->getResourceNode();
        if ($node) {
            $this->trackRepo->registerResourceEvent(
                $node,
                'edition',
                $this->security->getUser()?->getId()
            );
        }

        return $document;
    }
}
