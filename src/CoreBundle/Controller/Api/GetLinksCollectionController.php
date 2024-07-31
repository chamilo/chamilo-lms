<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CLinkCategoryRepository;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetLinksCollectionController extends BaseResourceFileAction
{
    public function __invoke(Request $request, CLinkRepository $repo, EntityManager $em, CLinkCategoryRepository $repoCategory): Response
    {
        $params = $request->query->all();
        $cid = $params['cid'] ?? null;
        $sid = $params['sid'] ?? null;
        $course = null;
        $session = null;
        $dataResponse = [];

        if ($cid) {
            $course = $em->getRepository(Course::class)->find($cid);
        }

        if ($sid) {
            $session = $em->getRepository(Session::class)->find($sid);
        }

        $qb = $repo->getResourcesByCourse($course, $session, null, null, true, true);
        $qb->andWhere('resource.category = 0 OR resource.category is null');
        $links = $qb->getQuery()->getResult();

        if ($links) {
            /** @var CLink $link */
            foreach ($links as $link) {
                $resourceNode = $link->getResourceNode();

                $dataResponse['linksWithoutCategory'][] = [
                    'id' => $link->getIid(),
                    'title' => $link->getTitle(),
                    'description' => $link->getDescription(),
                    'url' => $link->getUrl(),
                    'iid' => $link->getIid(),
                    'linkVisible' => $link->getFirstResourceLink()->getVisibility(),
                    'position' => $resourceNode->getResourceLinkByContext($course, $session)?->getDisplayOrder(),
                    'sessionId' => $resourceNode->getResourceLinkByContext($course, $session)?->getSession()?->getId(),
                ];
            }
        }

        $qb = $repoCategory->getResourcesByCourse($course, $session, null, null, true, true);
        $categories = $qb->getQuery()->getResult();
        if ($categories) {
            /** @var CLinkCategory $category */
            foreach ($categories as $category) {
                $categoryId = $category->getIid();
                $qbLink = $repo->getResourcesByCourse($course, $session);
                $qbLink->andWhere('resource.category = '.$categoryId);
                $links = $qbLink->getQuery()->getResult();

                $categoryInfo = [
                    'id' => $categoryId,
                    'title' => $category->getTitle(),
                    'description' => $category->getDescription(),
                    'visible' => $category->getFirstResourceLink()->getVisibility(),
                ];
                $dataResponse['categories'][$categoryId]['info'] = $categoryInfo;
                if ($links) {
                    $items = [];

                    /** @var CLink $link */
                    foreach ($links as $link) {
                        $resourceNode = $link->getResourceNode();

                        $items[] = [
                            'id' => $link->getIid(),
                            'title' => $link->getTitle(),
                            'description' => $link->getDescription(),
                            'url' => $link->getUrl(),
                            'iid' => $link->getIid(),
                            'linkVisible' => $link->getFirstResourceLink()->getVisibility(),
                            'position' => $resourceNode->getResourceLinkByContext($course, $session)?->getDisplayOrder(),
                            'sessionId' => $resourceNode->getResourceLinkByContext($course, $session)?->getSession()?->getId(),
                        ];

                        $dataResponse['categories'][$categoryId]['links'] = $items;
                    }
                }
            }
        }

        return new JsonResponse($dataResponse);
    }
}
