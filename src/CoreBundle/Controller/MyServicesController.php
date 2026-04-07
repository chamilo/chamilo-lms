<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Database;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/my-services')]
class MyServicesController extends AbstractController
{
    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * SPA shell — served by Symfony so the Vue router can boot.
     */
    #[Route('', name: 'chamilo_core_my_services')]
    public function index(): Response
    {
        return $this->render('@ChamiloCore/Vuejs/vue.html.twig');
    }

    /**
     * JSON data endpoint — consumed by MyServices.vue.
     */
    #[Route('-data', name: 'chamilo_core_my_services_data', methods: ['GET'])]
    public function data(): JsonResponse
    {
        $user = $this->userHelper->getCurrent();
        if (null === $user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $userId = $user->getId();
        $conn = $this->em->getConnection();

        $activeServices = $this->fetchActiveServices($conn, $userId);
        $purchaseHistory = $this->fetchPurchaseHistory($conn, $userId);

        return new JsonResponse([
            'activeServices' => $activeServices,
            'purchaseHistory' => $purchaseHistory,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchActiveServices(Connection $conn, int $userId): array
    {
        $saleTable = BuyCoursesPlugin::TABLE_SERVICES_SALE;
        $servicesTable = BuyCoursesPlugin::TABLE_SERVICES;

        return $conn->fetchAllAssociative(
            "SELECT ss.id, ss.date_start, ss.date_end, ss.price,
                    s.name, s.description, s.applies_to
             FROM {$saleTable} ss
             INNER JOIN {$servicesTable} s ON ss.service_id = s.id
             WHERE ss.buyer_id = ?
               AND ss.status = ?
               AND (ss.date_end IS NULL OR ss.date_end >= CURDATE())
             ORDER BY ss.date_end ASC",
            [$userId, BuyCoursesPlugin::SERVICE_STATUS_COMPLETED]
        );
    }

    /**
     * Unified purchase history (courses + sessions + services).
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchPurchaseHistory(Connection $conn, int $userId): array
    {
        $history = [];

        // Course purchases
        $courseSaleTable = BuyCoursesPlugin::TABLE_SALE;
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $rows = $conn->fetchAllAssociative(
            "SELECT sa.id, sa.buy_date as date, sa.price, sa.status,
                    c.title as product_name, 'course' as type
             FROM {$courseSaleTable} sa
             LEFT JOIN {$courseTable} c ON sa.item_id = c.id
             WHERE sa.buyer_id = ?
             ORDER BY sa.buy_date DESC",
            [$userId]
        );
        $history = array_merge($history, $rows);

        // Service purchases
        $serviceSaleTable = BuyCoursesPlugin::TABLE_SERVICES_SALE;
        $servicesTable = BuyCoursesPlugin::TABLE_SERVICES;
        $rows = $conn->fetchAllAssociative(
            "SELECT ss.id, ss.buy_date as date, ss.price, ss.status,
                    s.name as product_name, 'service' as type
             FROM {$serviceSaleTable} ss
             INNER JOIN {$servicesTable} s ON ss.service_id = s.id
             WHERE ss.buyer_id = ?
             ORDER BY ss.buy_date DESC",
            [$userId]
        );
        $history = array_merge($history, $rows);

        // Sort all by date desc
        usort($history, static fn (array $a, array $b): int => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));

        return $history;
    }
}
