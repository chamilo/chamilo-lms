<?php

declare(strict_types=1);

use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$stripeEnabled = 'true' === $plugin->get('stripe_enable');

if (!$stripeEnabled) {
    api_not_allowed(true);
}

$stripeParams = $plugin->getStripeParams();

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = Webhook::constructEvent(
        $payload,
        $sig_header,
        $stripeParams['endpoint_secret']
    );
} catch (UnexpectedValueException $e) {
    http_response_code(400);

    exit;
} catch (SignatureVerificationException $e) {
    http_response_code(400);

    exit;
}

switch ($event->type) {
    case 'checkout.session.completed':
        $checkoutSession = $event->data->object;

        $sale = $plugin->getSaleFromReference($checkoutSession->id);

        if (empty($sale)) {
            api_not_allowed(true);
        }

        $buyingCourse = false;
        $buyingSession = false;

        switch ($sale['product_type']) {
            case BuyCoursesPlugin::PRODUCT_TYPE_COURSE:
                $buyingCourse = true;
                $course = $plugin->getCourseInfo($sale['product_id']);

                break;

            case BuyCoursesPlugin::PRODUCT_TYPE_SESSION:
                $buyingSession = true;
                $session = $plugin->getSessionInfo($sale['product_id']);

                break;
        }

        $saleIsCompleted = $plugin->completeSale($sale['id']);

        if ($saleIsCompleted) {
            $plugin->storePayouts($sale['id']);
        }

        // no break
    default:
        echo 'Received unknown event type '.$event->type;
}

http_response_code(200);
