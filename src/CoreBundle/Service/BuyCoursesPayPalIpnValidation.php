<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service;

/**
 * PayPal requires the original IPN body, unchanged and in the original order.
 */
final class BuyCoursesPayPalIpnValidation
{
    public static function buildPostback(string $rawBody): ?string
    {
        if ('' === $rawBody) {
            return null;
        }

        return 'cmd=_notify-validate&'.$rawBody;
    }

    /**
     * Check a completed renewal against the merchant-authorized PayPal transaction.
     * Return a stable error code instead of exposing payment or account details.
     */
    public static function validateRecurringPayment(
        array $ipn,
        array $transaction,
        string $expectedAmount,
        string $expectedCurrency
    ): ?string {
        $transactionId = trim((string) ($ipn['txn_id'] ?? ''));
        if ('' === $transactionId
            || $transactionId !== trim((string) ($transaction['TRANSACTIONID'] ?? ''))
        ) {
            return 'transaction_id_mismatch';
        }

        if (!\in_array(strtoupper(trim((string) ($transaction['ACK'] ?? ''))), ['SUCCESS', 'SUCCESSWITHWARNING'], true)
            || 'COMPLETED' !== strtoupper(trim((string) ($transaction['PAYMENTSTATUS'] ?? '')))
            || 'completed' !== strtolower(trim((string) ($ipn['payment_status'] ?? '')))
        ) {
            return 'transaction_not_completed';
        }

        $ipnReceiverId = trim((string) ($ipn['receiver_id'] ?? ''));
        $apiReceiverId = trim((string) ($transaction['RECEIVERID'] ?? ''));
        $ipnReceiverEmail = strtolower(trim((string) ($ipn['receiver_email'] ?? '')));
        $apiReceiverEmail = strtolower(trim((string) ($transaction['RECEIVEREMAIL'] ?? '')));
        if ('' !== $ipnReceiverId && '' !== $apiReceiverId) {
            if (!hash_equals($apiReceiverId, $ipnReceiverId)) {
                return 'receiver_mismatch';
            }
        } elseif ('' === $ipnReceiverEmail || '' === $apiReceiverEmail
            || !hash_equals($apiReceiverEmail, $ipnReceiverEmail)
        ) {
            return 'receiver_mismatch';
        }

        $currency = strtoupper(trim($expectedCurrency));
        if (!preg_match('/^[A-Z]{3}$/', $currency)
            || $currency !== strtoupper(trim((string) ($ipn['mc_currency'] ?? '')))
            || $currency !== strtoupper(trim((string) ($transaction['CURRENCYCODE'] ?? '')))
        ) {
            return 'currency_mismatch';
        }

        $expectedCents = self::moneyToCents($expectedAmount);
        $ipnCents = self::moneyToCents((string) ($ipn['mc_gross'] ?? ''));
        $transactionCents = self::moneyToCents((string) ($transaction['AMT'] ?? ''));
        if (null === $expectedCents || $expectedCents <= 0
            || $expectedCents !== $ipnCents || $expectedCents !== $transactionCents
        ) {
            return 'amount_mismatch';
        }

        return null;
    }

    private static function moneyToCents(string $amount): ?int
    {
        if (!preg_match('/^([0-9]{1,9})(?:\.([0-9]{1,2}))?$/', trim($amount), $matches)) {
            return null;
        }

        return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    public static function hasCancelledRenewal(int $status, ?string $cancelledAt, int $cancelledStatus): bool
    {
        return $cancelledStatus === $status || '' !== trim((string) $cancelledAt);
    }
}
