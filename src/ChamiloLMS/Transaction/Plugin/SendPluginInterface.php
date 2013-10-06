<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction\Plugin;

use ChamiloLMS\Transaction\Envelope;
use Entity\BranchSync;

/**
 * Defines how an envelope is sent.
 */
interface SendPluginInterface extends PluginInterface
{
    /**
     * Sends a transactions envelope.
     *
     * @param Envelope $envelope
     *   The transactions envelope.
     *
     * @throws SendException
     *   When there is an error on the sending process.
     */
    public function send(Envelope $envelope);
}
