<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

class MyServicesController extends AbstractController
{
    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly PluginHelper $pluginHelper,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/my-services-data', name: 'chamilo_core_my_services_data', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function data(): JsonResponse
    {
        if (!$this->pluginHelper->isPluginEnabled('BuyCourses')) {
            return new JsonResponse(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->userHelper->getCurrent();
        if (null === $user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $plugin = BuyCoursesPlugin::create();
            $userId = $user->getId();

            return new JsonResponse([
                'activeServices' => $this->normalizeActiveServices($plugin->getActiveServicesForUser($userId)),
                'purchaseHistory' => $this->normalizePurchaseHistory($plugin->getPurchaseHistoryForUser($userId)),
            ]);
        } catch (Throwable $exception) {
            $this->logger->error('Unable to load my services data.', [
                'user_id' => $user->getId(),
                'exception_message' => $exception->getMessage(),
                'exception_class' => $exception::class,
            ]);

            return new JsonResponse([
                'activeServices' => [],
                'purchaseHistory' => [],
                'error' => 'Unable to load your services right now.',
            ]);
        }
    }

    private function normalizeActiveServices(array $rows): array
    {
        return array_map(static function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['service']['name'] ?? ''),
                'description' => (string) ($row['service']['description'] ?? ''),
                'dateStart' => (string) ($row['date_start'] ?? ''),
                'dateEnd' => (string) ($row['date_end'] ?? ''),
                'reference' => (string) ($row['reference'] ?? ''),
                'benefitSummaries' => array_map(static function (array $summary): array {
                    return [
                        'title' => (string) ($summary['title'] ?? ''),
                        'description' => (string) ($summary['description'] ?? ''),
                        'grantedValue' => (int) ($summary['granted_value'] ?? 0),
                        'unit' => (string) ($summary['unit'] ?? ''),
                        'activeSummary' => $summary['active_summary'] ?? null,
                    ];
                }, $row['benefit_summaries'] ?? []),
            ];
        }, $rows);
    }

    private function normalizePurchaseHistory(array $rows): array
    {
        return array_map(static function (array $row): array {
            return [
                'date' => (string) ($row['date'] ?? ''),
                'type' => (string) ($row['type'] ?? ''),
                'productName' => (string) ($row['product_name'] ?? ''),
                'reference' => (string) ($row['reference'] ?? ''),
                'amount' => (string) ($row['amount'] ?? ''),
                'status' => (int) ($row['status'] ?? 0),
                'receiptUrl' => $row['receipt_url'] ?? null,
            ];
        }, $rows);
    }
}
