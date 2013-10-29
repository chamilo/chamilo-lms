<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction\Plugin;

use ChamiloLMS\Transaction\Envelope;

/**
 * Customization to have an specific file name.
 */
class MineduAuthHttpsPostSend extends AuthHttpsPostSend
{

    /**
     * Base name for files to send for minedu.
     */
    const BASE_NAME='PE_0265';

    /**
     * {@inheritdoc}
     */
    public static function getMachineName()
    {
        return 'minedu_auth_https_post';
    }

    /**
     * A custom name for the teporary filename to send via curl.
     * Retrieves a temporary filename.
     */
    protected function getTemporaryFileToSend($name, Envelope $envelope) {
        // @fixme This is just wrong but it was requested. An Envelope nor any
        // place from here really knows which session_id to use because an
        // Envelope just contains a list of transactions, it does not matter if
        // they are part of one session or multiple ones.
        global $session_id;
        if ($name != 'blob_file') {
            return parent::getTemporaryFileToSend($name, $envelope);
        }
        // At blob_file request.
        $stamp = str_replace(array(' ', ':', '-'), '', api_get_datetime());
        $filename = sprintf('%s_%05d_%05d_%s_', self::BASE_NAME, $envelope->getOriginBranchId(), $session_id, $stamp);
        return parent::getTemporaryFileToSend($filename, $envelope);
    }
}
