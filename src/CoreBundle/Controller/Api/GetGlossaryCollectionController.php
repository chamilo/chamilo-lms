<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetGlossaryCollectionController extends BaseResourceFileAction
{
    public function __invoke(Request $request, CGlossaryRepository $repo, EntityManager $em): Response
    {
        $cid = $request->query->getInt('cid');
        $sid = $request->query->getInt('sid');
        $q = $request->query->get('q');
        $course = null;
        $session = null;
        if ($cid) {
            $course = $em->getRepository(Course::class)->find($cid);
        }

        if ($sid) {
            $session = $em->getRepository(Session::class)->find($sid);
        }

        $qb = $repo->getResourcesByCourse($course, $session);
        if ($q) {
            $qb->andWhere($qb->expr()->like('resource.name', ':name'))
                ->setParameter('name', '%'.$q.'%')
            ;
        }
        $glossaries = $qb->getQuery()->getResult();

        $dataResponse = [];
        if ($glossaries) {
            /** @var CGlossary $item */
            foreach ($glossaries as $item) {
                $dataResponse[] =
                    [
                        'iid' => $item->getIid(),
                        'id' => $item->getIid(),
                        'name' => $item->getName(),
                        'description' => $item->getDescription(),
                    ];
            }
        }

        return new JsonResponse($dataResponse);
    }
}
