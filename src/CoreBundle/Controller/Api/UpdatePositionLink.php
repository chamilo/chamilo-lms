<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UpdatePositionLink extends AbstractController
{
    public function __invoke(CLink $link, CLinkRepository $repo, Request $request, EntityManager $em): CLink
    {
        $requestData = json_decode($request->getContent(), true);
        $newPosition = (int) $requestData['position'];

        $resourceNode = $link->getResourceNode();
        if ($resourceNode) {
            $resourceNode->setDisplayOrder($newPosition);
            $em->flush();
        }

        return $link;
    }
}
