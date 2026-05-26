<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use BuyCoursesPlugin;
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
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/my-services-data', name: 'chamilo_core_my_services_data', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function data(): JsonResponse
    {
        if (!BuyCoursesPlugin::create()->isEnabled()) {
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
            $serviceSaleId = (int) ($row['id'] ?? 0);
            $serviceId = (int) ($row['service']['id'] ?? $row['service_id'] ?? 0);
            $paymentType = (int) ($row['payment_type'] ?? 0);
            $recurringPayment = (int) ($row['recurring_payment'] ?? BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_DISABLED);
            $isRenewable = 1 === (int) ($row['service']['renewable'] ?? $row['renewable'] ?? 0);
            $isPayPalPayment = BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL === $paymentType;

            $canEnableRecurringPayment = $isRenewable
                && $isPayPalPayment
                && BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED !== $recurringPayment;

            $canCancelRecurringPayment = $isRenewable
                && $isPayPalPayment
                && BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED === $recurringPayment
                && '' !== (string) ($row['recurring_profile_id'] ?? '');

            return [
                'id' => $serviceSaleId,
                'serviceId' => $serviceId,
                'name' => (string) ($row['service']['name'] ?? ''),
                'description' => (string) ($row['service']['description'] ?? ''),
                'dateStart' => (string) ($row['date_start'] ?? ''),
                'dateEnd' => (string) ($row['date_end'] ?? ''),
                'reference' => (string) ($row['reference'] ?? ''),
                'paymentType' => $paymentType,
                'isRenewable' => $isRenewable,
                'recurringPayment' => $recurringPayment,
                'recurringProfileId' => (string) ($row['recurring_profile_id'] ?? ''),
                'nextChargeDate' => (string) ($row['next_charge_date'] ?? ''),
                'cancelledAt' => (string) ($row['cancelled_at'] ?? ''),
                'canEnableRecurringPayment' => $canEnableRecurringPayment,
                'canCancelRecurringPayment' => $canCancelRecurringPayment,
                'infoUrl' => api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_information.php?service_id='.$serviceId.'&sale_id='.$serviceSaleId,
                'recurringPaymentUrl' => $canEnableRecurringPayment
                    ? api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/recurring_payment_process.php?action=enable_recurring_payment&order='.$serviceSaleId
                    : null,
                'cancelRecurringPaymentUrl' => $canCancelRecurringPayment
                    ? api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/recurring_payment_process.php?action=cancel_recurring_payment&order='.$serviceSaleId
                    : null,
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
