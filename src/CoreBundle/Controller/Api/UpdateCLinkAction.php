<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class UpdateCLinkAction extends BaseResourceFileAction
{
    public function __invoke(CLink $link, Request $request, CLinkRepository $repo, EntityManager $em): CLink
    {
        $data = json_decode($request->getContent(), true);
        $url = $data['url'];
        $title = $data['title'];
        $description = $data['description'];
        $categoryId = (int) $data['category'];
        $onHomepage = isset($data['showOnHomepage']) ? (int) $data['showOnHomepage'] : 0;
        $target = $data['target'];
        $parentResourceNodeId = $data['parentResourceNodeId'];
        $resourceLinkList = json_decode($data['resourceLinkList'], true);

        $link->setUrl($url);
        $link->setTitle($title);
        $link->setDescription($description);
        $link->setTarget($target);

        if (0 !== $categoryId) {
            $linkCategory = $em->getRepository(CLinkCategory::class)->find($categoryId);
            if ($linkCategory) {
                $link->setCategory($linkCategory);
            }
        }

        if (!empty($parentResourceNodeId)) {
            $link->setParentResourceNode($parentResourceNodeId);
        }

        if (!empty($resourceLinkList)) {
            $link->setResourceLinkArray($resourceLinkList);
        }

        return $link;
    }
}
