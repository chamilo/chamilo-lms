<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CLp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreateCLpAction extends BaseResourceFileAction
{
    public function __invoke(Request $request, EntityManagerInterface $em): CLp
    {
        $data = json_decode($request->getContent(), true);

        $cid = isset($data['cid']) ? (int) $data['cid'] : 0;
        if ($cid <= 0) {
            throw new BadRequestHttpException('Parameter "cid" (course identifier) is required.');
        }

        $title = $data['title'] ?? '';
        if ('' === $title) {
            throw new BadRequestHttpException('Parameter "title" is required.');
        }

        $lpType = isset($data['lpType']) ? (int) $data['lpType'] : CLp::LP_TYPE;
        $description = isset($data['description']) ? (string) $data['description'] : '';
        $sid = isset($data['sid']) ? (int) $data['sid'] : null;
        $gid = isset($data['gid']) ? (int) $data['gid'] : null;
        $visibility = isset($data['visibility']) ? (int) $data['visibility'] : 0;

        $course = $em->getRepository(Course::class)->find($cid);
        if (null === $course) {
            throw new NotFoundHttpException(\sprintf('Course #%d not found.', $cid));
        }

        $resourceNode = $course->getResourceNode();
        if (null === $resourceNode) {
            throw new NotFoundHttpException(\sprintf('Course #%d has no resource node.', $cid));
        }

        $lp = (new CLp())
            ->setTitle($title)
            ->setLpType($lpType)
            ->setDescription($description)
        ;

        $lp->setParentResourceNode($resourceNode->getId());

        $link = ['cid' => $cid, 'visibility' => $visibility];
        if (null !== $sid) {
            $link['sid'] = $sid;
        }
        if (null !== $gid) {
            $link['gid'] = $gid;
        }
        $lp->setResourceLinkArray([$link]);

        return $lp;
    }
}
