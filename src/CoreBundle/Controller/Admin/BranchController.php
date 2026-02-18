<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class BranchController extends BaseController
{
    #[Route('/admin/branches/with-counts', name: 'admin_branches_with_counts', methods: ['GET'])]
    public function withCounts(EntityManagerInterface $em): JsonResponse
    {
        $qb = $em->createQueryBuilder();
        $qb->select('b.id, b.title, b.description, COUNT(r.id) AS roomCount')
            ->from('Chamilo\CoreBundle\Entity\BranchSync', 'b')
            ->leftJoin('Chamilo\CoreBundle\Entity\Room', 'r', 'WITH', 'r.branch = b.id')
            ->groupBy('b.id')
            ->orderBy('b.title', 'ASC')
        ;

        return $this->json($qb->getQuery()->getArrayResult());
    }
}
