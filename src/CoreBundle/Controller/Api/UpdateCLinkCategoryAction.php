<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class UpdateCLinkCategoryAction extends BaseResourceFileAction
{
    public function __invoke(
        CLinkCategory $linkCategory,
        Request $request,
        CLinkRepository $repo,
        Security $security,
        EntityManagerInterface $em,
        CidReqHelper $cidReqHelper,
    ): CLinkCategory {
        $data = json_decode($request->getContent(), true);
        $title = $data['category_title'];
        $description = $data['description'];
        $parentResourceNodeId = $data['parentResourceNodeId'];
        $resourceLinkList = json_decode($data['resourceLinkList'], true);

        $linkCategory->setTitle($title);
        $linkCategory->setDescription($description);

        if (!empty($parentResourceNodeId)) {
            $this->assertParentNodeIsInCurrentCourse($cidReqHelper, (int) $parentResourceNodeId, $em);
            $linkCategory->setParentResourceNode($parentResourceNodeId);
        }

        if (!empty($resourceLinkList)) {
            $linkCategory->setResourceLinkArray($resourceLinkList);
        }

        return $linkCategory;
    }
}
