<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction\Plugin;

/**
 * Defines how a transaction is handled before going out of the system.
 */
interface WrapperPluginInterface extends PluginInterface
{
    /**
     * Wraps a list of transactions.
     *
     * @param array $transactions
     *   A list of \ChamiloLMS\Transaction\TransactionLog objects to wrap
     *   together. The transactions passed should be in export format, so this
     *   function does not require to call transaction export() method.
     *
     * @return string
     *   The wrapped envelope blob.
     *
     * @throws WrapException
     *   When there is an error on the wrapping process.
     */
    public function wrap($transactions);

    /**
     * Unwraps a list of transactions.
     *
     * @param string $envelope_blob
     *   The transactions wrapped with the corresponding wrap() method.
     *
     * @return array
     *   A set of \ChamiloLMS\Transaction\TransactionLog objects retrieved from
     *   the envelope blob.
     *
     * @throws UnwrapException
     *   When there is an error on the unwrapping process.
     */
    public function unwrap($envelope_blob);
}
