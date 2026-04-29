<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Career;
use Chamilo\CoreBundle\Entity\Promotion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CalendarCareerPromotionOptionsController extends AbstractController
{
    #[Route('/calendar/career-promotion-options', name: 'calendar_career_promotion_options', methods: ['GET'])]
    public function __invoke(EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $careers = $entityManager
            ->getRepository(Career::class)
            ->createQueryBuilder('career')
            ->select('career.id, career.title')
            ->where('career.status = :status')
            ->setParameter('status', Career::CAREER_STATUS_ACTIVE)
            ->orderBy('career.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $promotions = $entityManager
            ->getRepository(Promotion::class)
            ->createQueryBuilder('promotion')
            ->select('promotion.id, promotion.title, IDENTITY(promotion.career) AS careerId')
            ->where('promotion.status = :status')
            ->setParameter('status', Promotion::PROMOTION_STATUS_ACTIVE)
            ->orderBy('promotion.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        return new JsonResponse([
            'careers' => array_map(
                static fn (array $career): array => [
                    'id' => (int) $career['id'],
                    'title' => (string) $career['title'],
                ],
                $careers
            ),
            'promotions' => array_map(
                static fn (array $promotion): array => [
                    'id' => (int) $promotion['id'],
                    'title' => (string) $promotion['title'],
                    'careerId' => (int) $promotion['careerId'],
                ],
                $promotions
            ),
        ]);
    }
}
