<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction\Plugin;

/**
 * Uses JSON to wrap.
 *
 * @todo Maybe convert json error code into readable string.
 */
class JsonWrapper implements WrapperPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function wrap($transactions)
    {
        $blob = json_encode($transactions);
        $json_error = json_last_error();
        if ($json_error == JSON_ERROR_NONE) {
            return $blob;
        }
        $transaction_ids = array();
        foreach ($transactions as $transaction) {
            $transaction_ids[] = $transaction->id;
        }
        $message = sprintf('json: There was a problem(json error code "%d") wrapping the following transactions: %s.', $json_error, implode(',', $transaction_ids));
        throw new WrapException($message);
    }

    /**
     * {@inheritdoc}
     */
    public function unwrap($envelope_blob)
    {
        $transactions = json_decode($envelope_blob);
        $json_error = json_last_error();
        if ($json_error == JSON_ERROR_NONE) {
            return $transactions;
        }
        $message = sprintf('json: There was a problem(json error code "%d") unwrapping the blob: %s.', $json_error, $envelope_blob);
        throw new UnwrapException($message);
    }
}
