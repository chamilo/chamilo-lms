<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction\Plugin;

use ChamiloLMS\Transaction\Envelope;
use Entity\BranchSync;

/**
 * Defines how an envelope is sent.
 */
interface SendPluginInterface
{
    /**
     * Sends the transactions envelope to the destination system.
     *
     * @param Envelope $envelope
     *   The transactions envelope.
     * @param BranchSync $branch
     *   The destination branch where to send the envelope.
     *
     * @throws SendException
     *   When there is an error on the sending process.
     */
    public function send(Envelope $envelope, BranchSync $branch);
}
