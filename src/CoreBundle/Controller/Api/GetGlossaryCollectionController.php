<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class GetGlossaryCollectionController extends BaseResourceFileAction
{
    public function __construct(
        private readonly AiDisclosureHelper $aiDisclosureHelper,
    ) {}

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

        $qb = $repo->getResourcesByCourse($course, $session, null, null, true, true);
        if ($q) {
            $qb->andWhere($qb->expr()->like('resource.title', ':title'))
                ->setParameter('title', '%'.$q.'%')
            ;
        }
        $glossaries = $qb->getQuery()->getResult();
        $discloseEnabled = $this->aiDisclosureHelper->isDisclosureEnabled();
        $dataResponse = [];
        foreach ($glossaries as $item) {
            if (!$item instanceof CGlossary) {
                continue;
            }

            $iid = (int) ($item->getIid() ?? 0);
            $raw = $iid > 0 ? $this->aiDisclosureHelper->isAiAssistedExtraField('glossary', $iid) : false;
            $dataResponse[] = [
                'iid' => $iid,
                'id' => $iid,
                'title' => $item->getTitle(),
                'description' => $item->getDescription(),
                'sessionId' => $item->getFirstResourceLink()->getSession()
                    ? $item->getFirstResourceLink()->getSession()->getId()
                    : null,
                'ai_assisted_raw' => (bool) $raw,
                'ai_assisted' => $discloseEnabled && (bool) $raw,
            ];
        }

        return new JsonResponse($dataResponse);
    }
}
