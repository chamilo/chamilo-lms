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
class RoomController extends BaseController
{
    #[Route('/admin/rooms/with-counts', name: 'admin_rooms_with_counts', methods: ['GET'])]
    public function withCounts(EntityManagerInterface $em): JsonResponse
    {
        $qb = $em->createQueryBuilder();
        $qb->select('r.id, r.title, r.description, b.id AS branchId, b.title AS branchTitle, COUNT(c.id) AS courseCount')
            ->from('Chamilo\CoreBundle\Entity\Room', 'r')
            ->leftJoin('r.branch', 'b')
            ->leftJoin('Chamilo\CoreBundle\Entity\Course', 'c', 'WITH', 'c.room = r.id')
            ->groupBy('r.id')
            ->orderBy('r.title', 'ASC')
        ;

        $results = $qb->getQuery()->getArrayResult();

        $data = array_map(function ($row) {
            return [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'branch' => [
                    'id' => $row['branchId'],
                    'title' => $row['branchTitle'],
                ],
                'courseCount' => (int) $row['courseCount'],
            ];
        }, $results);

        return $this->json($data);
    }
}
