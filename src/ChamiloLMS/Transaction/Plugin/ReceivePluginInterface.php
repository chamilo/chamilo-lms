<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction\Plugin;

/**
 * Defines how envelopes are received.
 */
interface ReceivePluginInterface
{
    /**
     * Receives envelopes for the current system.
     *
     * @param integer $limit
     *   The maximum allowed envelopes to receive. 0 means unlimited.
     *
     * @return array
     *   A list of envelope objects received.
     *
     * @throws ReceiveException
     *   When there is an error on the receiving process.
     */
    public function receive($limit = 0);
}
