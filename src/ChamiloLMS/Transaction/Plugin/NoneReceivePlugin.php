<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction\Plugin;

/**
 * No receive plugin.
 *
 * It is used when receiving is going to be handled manually.
 */
class NoneReceivePlugin implements ReceivePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function receive($limit = 0)
    {
        // Do nothing.
    }
}
