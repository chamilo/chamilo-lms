<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\RoomAccessUrlHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RoomController extends BaseController
{
    #[Route('/admin/rooms/with-counts', name: 'admin_rooms_with_counts', methods: ['GET'])]
    public function withCounts(EntityManagerInterface $em, AccessUrlHelper $accessUrlHelper): JsonResponse
    {
        $accessUrlId = $accessUrlHelper->getCurrent()?->getId();
        if (null === $accessUrlId) {
            return $this->json([]);
        }
        $qb = $em->createQueryBuilder();
        $qb->select('r.id, r.title, r.description, r.floorNumber, r.capacity, b.id AS branchId, b.title AS branchTitle, COUNT(c.id) AS courseCount')
            ->from('Chamilo\CoreBundle\Entity\Room', 'r')
            ->leftJoin('r.branch', 'b')
            ->leftJoin('Chamilo\CoreBundle\Entity\Course', 'c', 'WITH', 'c.room = r.id')
            ->where('IDENTITY(b.url) = :accessUrlId')
            ->setParameter('accessUrlId', $accessUrlId)
            ->groupBy('r.id')
            ->orderBy('r.title', 'ASC')
        ;

        $results = $qb->getQuery()->getArrayResult();

        $data = array_map(function ($row) {
            return [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'floorNumber' => $row['floorNumber'],
                'capacity' => $row['capacity'],
                'branch' => [
                    'id' => $row['branchId'],
                    'title' => $row['branchTitle'],
                ],
                'courseCount' => (int) $row['courseCount'],
            ];
        }, $results);

        return $this->json($data);
    }

    #[Route('/admin/rooms/{id}/courses', name: 'admin_room_courses', methods: ['GET'])]
    public function courses(Room $room, EntityManagerInterface $em, RoomAccessUrlHelper $roomAccessUrlHelper): JsonResponse
    {
        if (!$roomAccessUrlHelper->isRoomAllowed($room)) {
            throw new NotFoundHttpException();
        }
        $qb = $em->createQueryBuilder();
        $qb->select('c.id, c.title, c.code')
            ->from('Chamilo\CoreBundle\Entity\Course', 'c')
            ->where('c.room = :roomId')
            ->setParameter('roomId', $room->getId())
            ->orderBy('c.title', 'ASC')
        ;

        $results = $qb->getQuery()->getArrayResult();

        return $this->json($results);
    }
}
