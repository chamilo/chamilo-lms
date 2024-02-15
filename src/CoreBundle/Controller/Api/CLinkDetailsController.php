<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CLinkDetailsController extends AbstractController
{
    public function __invoke(CLink $link, CShortcutRepository $shortcutRepository): Response
    {
        $shortcut = $shortcutRepository->getShortcutFromResource($link);
        $isOnHomepage = null !== $shortcut;

        $parentResourceNodeId = null;
        if ($link->getResourceNode() && $link->getResourceNode()->getParent()) {
            $parentResourceNodeId = $link->getResourceNode()->getParent()->getId();
        }

        $resourceLinkList = [];
        if ($link->getResourceLinkEntityList()) {
            foreach ($link->getResourceLinkEntityList() as $resourceLink) {
                $resourceLinkList[] = [
                    'visibility' => $resourceLink->getVisibility(),
                    'cid' => $resourceLink->getCourse()->getId(),
                    'sid' => $resourceLink->getSession()->getId(),
                ];
            }
        }

        $details = [
            'url' => $link->getUrl(),
            'title' => $link->getTitle(),
            'description' => $link->getDescription(),
            'onHomepage' => $isOnHomepage,
            'target' => $link->getTarget(),
            'parentResourceNodeId' => $parentResourceNodeId,
            'resourceLinkList' => $resourceLinkList,
            'category' => $link->getCategory()?->getIid(),
        ];

        return $this->json($details, Response::HTTP_OK);
    }
}
