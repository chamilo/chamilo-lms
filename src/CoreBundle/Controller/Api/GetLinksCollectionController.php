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
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

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

        $qb = $repo->getResourcesByCourse($course, $session);
        $qb->andWhere('resource.category = 0 OR resource.category is null');
        $qb->addOrderBy('resource.displayOrder', 'ASC');
        $links = $qb->getQuery()->getResult();

        if ($links) {
            /* @var CLink $link */
            foreach ($links as $link) {
                $dataResponse['linksWithoutCategory'][] =
                  [
                      'id' => $link->getIid(),
                      'title' => $link->getTitle(),
                      'url' => $link->getUrl(),
                      'iid' => $link->getIid(),
                      'linkVisible' => $link->getFirstResourceLink()->getVisibility(),
                      'position' => $link->getDisplayOrder(),
                  ];
            }
        }

        $qb = $repoCategory->getResourcesByCourse($course, $session);
        $categories = $qb->getQuery()->getResult();
        if ($categories) {
            /* @var CLinkCategory $category */
            foreach ($categories as $category) {
                $categoryId = $category->getIid();
                $qbLink = $repo->getResourcesByCourse($course, $session);
                $qbLink->andWhere('resource.category = '.$categoryId);
                $qbLink->addOrderBy('resource.displayOrder', 'ASC');
                $links = $qbLink->getQuery()->getResult();

                $categoryInfo = [
                    'id' => $categoryId,
                    'name' => $category->getCategoryTitle(),
                    'visible' => $category->getFirstResourceLink()->getVisibility(),
                ];
                $dataResponse['categories'][$categoryId]['info'] = $categoryInfo;
                if ($links) {
                    $items = [];
                    /* @var CLink $link */
                    foreach ($links as $link) {

                        $items[] = [
                                'id' => $link->getIid(),
                                'title' => $link->getTitle(),
                                'url' => $link->getUrl(),
                                'iid' => $link->getIid(),
                                'linkVisible' => $link->getFirstResourceLink()->getVisibility(),
                                'position' => $link->getDisplayOrder(),
                            ];

                        $dataResponse['categories'][$categoryId]['links'] = $items;
                    }
                }
            }
        }

        return new JsonResponse($dataResponse);
    }
}
