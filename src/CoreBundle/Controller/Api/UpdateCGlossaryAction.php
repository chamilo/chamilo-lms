<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UpdateCGlossaryAction extends BaseResourceFileAction
{
    public function __invoke(CGlossary $glossary, Request $request, CGlossaryRepository $repo, EntityManager $em): CGlossary
    {
        $data = json_decode($request->getContent(), true);
        $title = $data['name'];
        $description = $data['description'];
        $parentResourceNodeId = $data['parentResourceNodeId'];
        $resourceLinkList = json_decode($data['resourceLinkList'], true);
        $sid = (int) $data['sid'];
        $cid = (int) $data['cid'];

        $course = null;
        $session = null;
        if (0 !== $cid) {
            $course = $em->getRepository(Course::class)->find($cid);
        }
        if (0 !== $sid) {
            $session = $em->getRepository(Session::class)->find($sid);
        }

        // Check if the term already exists
        $qb = $repo->getResourcesByCourse($course, $session)
            ->andWhere('resource.name = :name')
            ->setParameter('name', $title);
        $existingGlossaryTerm = $qb->getQuery()->getOneOrNullResult();
        if ($existingGlossaryTerm !== null && $existingGlossaryTerm->getIid() !== $glossary->getIid()) {
            throw new BadRequestHttpException('The glossary term already exists.');
        }

        $glossary->setName($title);
        $glossary->setDescription($description);

        if (!empty($parentResourceNodeId)) {
            $glossary->setParentResourceNode($parentResourceNodeId);
        }

        if (!empty($resourceLinkList)) {
            $glossary->setResourceLinkArray($resourceLinkList);
        }

        return $glossary;
    }
}
