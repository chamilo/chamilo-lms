<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$stripeEnabled = 'true' === $plugin->get('stripe_enable');

if (!$stripeEnabled) {
    api_not_allowed(true);
}

$stripeParams = $plugin->getStripeParams();
$endpointSecret = trim((string) ($stripeParams['endpoint_secret'] ?? ''));

$payload = (string) @file_get_contents('php://input');
$signatureHeader = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');

$respond = static function (string $message, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
};

$log = static function (string $message, array $context = []): void {
    $suffix = '';
    if ([] !== $context) {
        $suffix = ' | '.json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    error_log('[BuyCourses][Stripe] '.$message.$suffix);
};

if ('' === $payload || '' === $signatureHeader || '' === $endpointSecret) {
    $log('Webhook rejected because payload, signature or endpoint secret is missing.');
    $respond('BAD_REQUEST', 400);
}

try {
    $event = Webhook::constructEvent(
        $payload,
        $signatureHeader,
        $endpointSecret
    );
} catch (UnexpectedValueException $exception) {
    $log('Invalid webhook payload.', ['error' => $exception->getMessage()]);
    $respond('INVALID_PAYLOAD', 400);
} catch (SignatureVerificationException $exception) {
    $log('Invalid webhook signature.', ['error' => $exception->getMessage()]);
    $respond('INVALID_SIGNATURE', 400);
}

$eventId = (string) ($event->id ?? '');
$eventType = (string) ($event->type ?? '');
$object = $event->data->object ?? null;

$timestampToDateTime = static function (mixed $timestamp): ?string {
    $timestamp = (int) $timestamp;
    if ($timestamp <= 0) {
        return null;
    }

    return gmdate('Y-m-d H:i:s', $timestamp);
};

$getStripeObjectValue = static function (mixed $object, string $path): mixed {
    $current = $object;
    foreach (explode('.', $path) as $segment) {
        if ('' === $segment) {
            continue;
        }

        if (is_array($current)) {
            if (!array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
            continue;
        }

        if (!is_object($current) || !isset($current->{$segment})) {
            return null;
        }

        $current = $current->{$segment};
    }

    return $current;
};

$getStripeMetadataValue = static function (mixed $object, string $key) use ($getStripeObjectValue): ?string {
    $metadataLocations = [
        'metadata',
        'subscription_details.metadata',
        'parent.subscription_details.metadata',
    ];

    foreach ($metadataLocations as $path) {
        $metadata = $getStripeObjectValue($object, $path);
        if (is_array($metadata) && isset($metadata[$key])) {
            return (string) $metadata[$key];
        }

        if (is_object($metadata) && isset($metadata->{$key})) {
            return (string) $metadata->{$key};
        }
    }

    return null;
};

$getStripeSubscriptionId = static function (mixed $object) use ($getStripeObjectValue): string {
    $candidatePaths = [
        'subscription',
        'subscription_details.subscription',
        'parent.subscription_details.subscription',
    ];

    foreach ($candidatePaths as $path) {
        $value = $getStripeObjectValue($object, $path);
        if (is_string($value) && '' !== trim($value)) {
            return trim($value);
        }
    }

    $lines = $object->lines->data ?? [];
    if (is_iterable($lines)) {
        foreach ($lines as $line) {
            $lineCandidatePaths = [
                'subscription',
                'parent.subscription_item_details.subscription',
                'subscription_item_details.subscription',
            ];

            foreach ($lineCandidatePaths as $path) {
                $value = $getStripeObjectValue($line, $path);
                if (is_string($value) && '' !== trim($value)) {
                    return trim($value);
                }
            }
        }
    }

    return '';
};

$getInvoicePeriodEnd = static function (mixed $invoice) use ($timestampToDateTime, $getStripeObjectValue): ?string {
    if (!$invoice) {
        return null;
    }

    $lines = $invoice->lines->data ?? [];
    if (is_iterable($lines)) {
        foreach ($lines as $line) {
            $candidatePaths = [
                'period.end',
                'parent.subscription_item_details.current_period_end',
                'subscription_item_details.current_period_end',
            ];

            foreach ($candidatePaths as $path) {
                $date = $timestampToDateTime($getStripeObjectValue($line, $path));
                if (null !== $date) {
                    return $date;
                }
            }
        }
    }

    foreach (['period_end', 'subscription_details.current_period_end', 'parent.subscription_details.current_period_end'] as $path) {
        $date = $timestampToDateTime($getStripeObjectValue($invoice, $path));
        if (null !== $date) {
            return $date;
        }
    }

    return null;
};

$completeCourseOrSessionSale = static function (mixed $checkoutSession) use ($plugin, $log): bool {
    $checkoutSessionId = (string) ($checkoutSession->id ?? '');
    if ('' === $checkoutSessionId) {
        return false;
    }

    $sale = $plugin->getSaleFromReference($checkoutSessionId);
    if (empty($sale)) {
        return false;
    }

    $saleIsCompleted = $plugin->completeSale((int) $sale['id']);
    if ($saleIsCompleted) {
        $plugin->storePayouts((int) $sale['id']);
    }

    $log('Course/session Stripe sale completed.', [
        'sale_id' => (int) $sale['id'],
        'checkout_session_id' => $checkoutSessionId,
    ]);

    return true;
};

$completeServiceSaleFromCheckout = static function (mixed $checkoutSession) use ($plugin, $timestampToDateTime, $log): bool {
    $checkoutSessionId = (string) ($checkoutSession->id ?? '');
    if ('' === $checkoutSessionId) {
        return false;
    }

    $serviceSale = $plugin->getServiceSaleFromGatewayCheckoutSessionId($checkoutSessionId);
    if (empty($serviceSale)) {
        return false;
    }

    $serviceSaleId = (int) $serviceSale['id'];
    if ($plugin->wasGatewayEventProcessed($serviceSaleId, (string) ($checkoutSession->id ?? ''))) {
        return true;
    }

    $subscriptionId = isset($checkoutSession->subscription) ? (string) $checkoutSession->subscription : null;
    $customerId = isset($checkoutSession->customer) ? (string) $checkoutSession->customer : null;

    $gatewayData = [
        'gateway_customer_id' => $customerId,
    ];

    if (null !== $subscriptionId && '' !== $subscriptionId) {
        $gatewayData['gateway_subscription_id'] = $subscriptionId;
        $gatewayData['recurring_profile_id'] = $subscriptionId;
        $gatewayData['recurring_gateway'] = 'stripe';
        $gatewayData['recurring_payment'] = BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED;
    }

    $plugin->updateServiceSaleGatewayData($serviceSaleId, $gatewayData);
    $plugin->completeServiceSale($serviceSaleId);
    $plugin->applyServiceBenefitsFromSale($serviceSaleId);

    $completedServiceSale = $plugin->getServiceSale($serviceSaleId);
    $nextChargeDate = trim((string) ($completedServiceSale['date_end'] ?? ''));
    if ('' !== $nextChargeDate && null !== $subscriptionId && '' !== $subscriptionId) {
        $plugin->updateServiceSaleGatewayData($serviceSaleId, [
            'date_end' => $nextChargeDate,
            'next_charge_date' => $nextChargeDate,
        ]);
    }

    $plugin->markGatewayEventProcessed($serviceSaleId, (string) ($checkoutSession->id ?? ''));

    $log('Service Stripe checkout completed.', [
        'service_sale_id' => $serviceSaleId,
        'checkout_session_id' => $checkoutSessionId,
        'subscription_id' => $subscriptionId,
        'next_charge_date' => $nextChargeDate,
    ]);

    return true;
};

switch ($eventType) {
    case 'checkout.session.completed':
        if (!$completeCourseOrSessionSale($object)) {
            $completeServiceSaleFromCheckout($object);
        }
        break;

    case 'invoice.paid':
        $subscriptionId = $getStripeSubscriptionId($object);
        $serviceSaleIdFromMetadata = (int) ($getStripeMetadataValue($object, 'service_sale_id') ?? 0);
        $serviceSale = [];

        if ('' !== $subscriptionId) {
            $serviceSale = $plugin->getServiceSaleFromGatewaySubscriptionId($subscriptionId);
        }

        if (empty($serviceSale) && $serviceSaleIdFromMetadata > 0) {
            $serviceSale = $plugin->getServiceSale($serviceSaleIdFromMetadata);
            if ('' === $subscriptionId) {
                $subscriptionId = (string) ($serviceSale['gateway_subscription_id'] ?? $serviceSale['recurring_profile_id'] ?? '');
            }
        }

        if ('' === $subscriptionId) {
            $log('invoice.paid ignored because no subscription ID was found.', [
                'event_id' => $eventId,
                'service_sale_id' => $serviceSaleIdFromMetadata,
            ]);
            break;
        }

        if (empty($serviceSale)) {
            $log('invoice.paid ignored because no service sale matches subscription.', [
                'event_id' => $eventId,
                'subscription_id' => $subscriptionId,
                'service_sale_id' => $serviceSaleIdFromMetadata,
            ]);
            break;
        }

        $serviceSaleId = (int) $serviceSale['id'];
        if ($plugin->wasGatewayEventProcessed($serviceSaleId, $eventId)) {
            $log('Duplicate Stripe invoice.paid ignored.', [
                'service_sale_id' => $serviceSaleId,
                'event_id' => $eventId,
            ]);
            break;
        }

        $nextChargeDate = $getInvoicePeriodEnd($object);
        if (null === $nextChargeDate) {
            $nextChargeDate = trim((string) ($serviceSale['date_end'] ?? '')) ?: null;
        }

        $customerId = isset($object->customer) ? (string) $object->customer : null;

        $plugin->completeStripeRecurringServiceSale(
            $serviceSaleId,
            $subscriptionId,
            $customerId,
            $nextChargeDate
        );

        $updateData = [
            'recurring_payment' => BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED,
            'recurring_gateway' => 'stripe',
            'gateway_subscription_id' => $subscriptionId,
            'recurring_profile_id' => $subscriptionId,
        ];

        if (null !== $nextChargeDate) {
            $updateData['date_end'] = $nextChargeDate;
            $updateData['next_charge_date'] = $nextChargeDate;
        }

        if (null !== $customerId && '' !== $customerId) {
            $updateData['gateway_customer_id'] = $customerId;
        }

        $plugin->updateServiceSaleGatewayData($serviceSaleId, $updateData);
        $plugin->applyServiceBenefitsFromSale($serviceSaleId);
        $plugin->markGatewayEventProcessed($serviceSaleId, $eventId);

        $log('Stripe recurring invoice paid.', [
            'service_sale_id' => $serviceSaleId,
            'subscription_id' => $subscriptionId,
            'next_charge_date' => $nextChargeDate,
        ]);
        break;

    case 'invoice.payment_failed':
        $subscriptionId = $getStripeSubscriptionId($object);
        $serviceSaleIdFromMetadata = (int) ($getStripeMetadataValue($object, 'service_sale_id') ?? 0);
        $serviceSale = [];

        if ('' !== $subscriptionId) {
            $serviceSale = $plugin->getServiceSaleFromGatewaySubscriptionId($subscriptionId);
        }

        if (empty($serviceSale) && $serviceSaleIdFromMetadata > 0) {
            $serviceSale = $plugin->getServiceSale($serviceSaleIdFromMetadata);
            if ('' === $subscriptionId) {
                $subscriptionId = (string) ($serviceSale['gateway_subscription_id'] ?? $serviceSale['recurring_profile_id'] ?? '');
            }
        }

        if (!empty($serviceSale)) {
            $plugin->updateServiceSaleGatewayData((int) $serviceSale['id'], [
                'recurring_payment' => BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_SUSPENDED,
                'recurring_gateway' => 'stripe',
                'gateway_subscription_id' => $subscriptionId,
                'recurring_profile_id' => $subscriptionId,
            ]);

            $log('Stripe recurring invoice payment failed. Sale marked as suspended.', [
                'service_sale_id' => (int) $serviceSale['id'],
                'subscription_id' => $subscriptionId,
            ]);
        }
        break;

    case 'customer.subscription.deleted':
        $subscriptionId = (string) ($object->id ?? '');
        if ('' === $subscriptionId) {
            break;
        }

        $serviceSale = $plugin->getServiceSaleFromGatewaySubscriptionId($subscriptionId);
        if (!empty($serviceSale)) {
            $plugin->updateServiceSaleGatewayData((int) $serviceSale['id'], [
                'recurring_payment' => BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_CANCELLED,
                'cancelled_at' => api_get_utc_datetime(),
                'recurring_gateway' => 'stripe',
                'gateway_subscription_id' => $subscriptionId,
                'recurring_profile_id' => $subscriptionId,
            ]);

            $log('Stripe subscription deleted.', [
                'service_sale_id' => (int) $serviceSale['id'],
                'subscription_id' => $subscriptionId,
            ]);
        }
        break;

    default:
        $log('Stripe event ignored.', [
            'event_id' => $eventId,
            'event_type' => $eventType,
        ]);
}

$respond('OK');
