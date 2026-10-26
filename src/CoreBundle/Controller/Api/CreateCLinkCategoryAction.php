<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class CreateCLinkCategoryAction extends BaseResourceFileAction
{
    public function __invoke(Request $request, CLinkRepository $repo, EntityManager $em, CidReqHelper $cidReqHelper): CLinkCategory
    {
        $data = json_decode($request->getContent(), true);
        $title = $data['category_title'];
        $description = $data['description'];
        $parentResourceNodeId = $data['parentResourceNodeId'];
        $resourceLinkList = $this->buildResourceLinkListFromContext(
            $cidReqHelper,
            (array) json_decode((string) ($data['resourceLinkList'] ?? '[]'), true),
        );

        $linkCategory = (new CLinkCategory())
            ->setTitle($title)
            ->setDescription($description)
        ;

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
