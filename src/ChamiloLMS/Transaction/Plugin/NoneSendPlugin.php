<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction\Plugin;

use ChamiloLMS\Transaction\Envelope;
use Entity\BranchSync;

/**
 * No send plugin.
 *
 * It is used when sending is going to be handled manually.
 */
class NoneSendPlugin implements SendPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function send(Envelope $envelope, BranchSync $branch)
    {
        // Do nothing.
    }
}
