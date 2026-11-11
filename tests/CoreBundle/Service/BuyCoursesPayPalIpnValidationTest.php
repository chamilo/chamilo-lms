<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Service\BuyCoursesPayPalIpnValidation;
use PHPUnit\Framework\TestCase;

final class BuyCoursesPayPalIpnValidationTest extends TestCase
{
    public function testPostbackPreservesOriginalEncodedPayloadAndOrdering(): void
    {
        $rawBody = 'txn_type=recurring_payment&payment_date=12%3A30%3A00+Nov+11%2C+2026&custom=a%2Bb&mc_gross=10.00';

        self::assertSame(
            'cmd=_notify-validate&'.$rawBody,
            BuyCoursesPayPalIpnValidation::buildPostback($rawBody)
        );
    }

    public function testEmptyOriginalPayloadCannotBeValidated(): void
    {
        self::assertNull(BuyCoursesPayPalIpnValidation::buildPostback(''));
    }

    public function testLatePaymentDoesNotUndoCancelledRenewal(): void
    {
        self::assertTrue(BuyCoursesPayPalIpnValidation::hasCancelledRenewal(-1, null, -1));
        self::assertTrue(BuyCoursesPayPalIpnValidation::hasCancelledRenewal(1, '2026-11-10 00:00:00', -1));
        self::assertFalse(BuyCoursesPayPalIpnValidation::hasCancelledRenewal(1, null, -1));
    }
    private function validPayment(): array
    {
        return [
            'ipn' => [
                'txn_id' => '7BK123456789',
                'payment_status' => 'Completed',
                'receiver_id' => 'MERCHANT123',
                'receiver_email' => 'seller@example.test',
                'mc_gross' => '25.50',
                'mc_currency' => 'USD',
            ],
            'details' => [
                'ACK' => 'Success',
                'TRANSACTIONID' => '7BK123456789',
                'PAYMENTSTATUS' => 'Completed',
                'RECEIVERID' => 'MERCHANT123',
                'RECEIVEREMAIL' => 'seller@example.test',
                'AMT' => '25.50',
                'CURRENCYCODE' => 'USD',
            ],
        ];
    }

    public function testVerifiedMerchantPaymentMatchesLocalSale(): void
    {
        $payment = $this->validPayment();
        self::assertNull(BuyCoursesPayPalIpnValidation::validateRecurringPayment(
            $payment['ipn'],
            $payment['details'],
            '25.50',
            'USD'
        ));
    }

    public function testDifferentReceiverIsRejected(): void
    {
        $payment = $this->validPayment();
        $payment['ipn']['receiver_id'] = 'ANOTHER123';
        self::assertSame('receiver_mismatch', BuyCoursesPayPalIpnValidation::validateRecurringPayment(
            $payment['ipn'],
            $payment['details'],
            '25.50',
            'USD'
        ));
    }

    public function testIncorrectAmountIsRejected(): void
    {
        $payment = $this->validPayment();
        $payment['ipn']['mc_gross'] = '2.55';
        self::assertSame('amount_mismatch', BuyCoursesPayPalIpnValidation::validateRecurringPayment(
            $payment['ipn'],
            $payment['details'],
            '25.50',
            'USD'
        ));
    }

    public function testIncorrectCurrencyIsRejected(): void
    {
        $payment = $this->validPayment();
        $payment['details']['CURRENCYCODE'] = 'EUR';
        self::assertSame('currency_mismatch', BuyCoursesPayPalIpnValidation::validateRecurringPayment(
            $payment['ipn'],
            $payment['details'],
            '25.50',
            'USD'
        ));
    }

    public function testPaymentThatWasNotCompletedIsRejected(): void
    {
        $payment = $this->validPayment();
        $payment['details']['PAYMENTSTATUS'] = 'Pending';
        self::assertSame('transaction_not_completed', BuyCoursesPayPalIpnValidation::validateRecurringPayment(
            $payment['ipn'],
            $payment['details'],
            '25.50',
            'USD'
        ));
    }

    public function testAnotherTransactionIsRejected(): void
    {
        $payment = $this->validPayment();
        $payment['details']['TRANSACTIONID'] = 'OTHER';
        self::assertSame('transaction_id_mismatch', BuyCoursesPayPalIpnValidation::validateRecurringPayment(
            $payment['ipn'],
            $payment['details'],
            '25.50',
            'USD'
        ));
    }

    public function testReceiverEmailIsUsedWhenReceiverIdsAreMissing(): void
    {
        $payment = $this->validPayment();
        unset($payment['ipn']['receiver_id'], $payment['details']['RECEIVERID']);
        self::assertNull(BuyCoursesPayPalIpnValidation::validateRecurringPayment(
            $payment['ipn'],
            $payment['details'],
            '25.50',
            'USD'
        ));
        $payment['ipn']['receiver_email'] = 'wrong@example.test';
        self::assertSame('receiver_mismatch', BuyCoursesPayPalIpnValidation::validateRecurringPayment(
            $payment['ipn'],
            $payment['details'],
            '25.50',
            'USD'
        ));
    }

    public function testMalformedPaymentAmountIsRejected(): void
    {
        $payment = $this->validPayment();
        $payment['ipn']['mc_gross'] = '25.500';
        self::assertSame('amount_mismatch', BuyCoursesPayPalIpnValidation::validateRecurringPayment(
            $payment['ipn'],
            $payment['details'],
            '25.50',
            'USD'
        ));
    }
    public function testMerchantAndIpnAgreementIsNotEnoughIfSalePriceDiffers(): void
    {
        $payment = $this->validPayment();
        self::assertSame('amount_mismatch', BuyCoursesPayPalIpnValidation::validateRecurringPayment(
            $payment['ipn'],
            $payment['details'],
            '30.00',
            'USD'
        ));
    }
}
