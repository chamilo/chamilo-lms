<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class CreateCLinkCategoryAction extends BaseResourceFileAction
{
    public function __invoke(Request $request, CLinkRepository $repo, EntityManager $em): CLinkCategory
    {
        $data = json_decode($request->getContent(), true);
        $title = $data['category_title'];
        $description = $data['description'];
        $parentResourceNodeId = $data['parentResourceNodeId'];
        $resourceLinkList = json_decode($data['resourceLinkList'], true);

        $linkCategory = (new CLinkCategory())
            ->setCategoryTitle($title)
            ->setDescription($description)
        ;

        if (!empty($parentResourceNodeId)) {
            $linkCategory->setParentResourceNode($parentResourceNodeId);
        }

        if (!empty($resourceLinkList)) {
            $linkCategory->setResourceLinkArray($resourceLinkList);
        }

        return $linkCategory;
    }
}
