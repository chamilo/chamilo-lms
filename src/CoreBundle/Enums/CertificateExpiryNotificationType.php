<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Enums;

/**
 * The kind of reminder sent to a learner about a certificate's expiry.
 */
enum CertificateExpiryNotificationType: string
{
    /**
     * Sent while the certificate has not expired yet, within the configured warning window.
     */
    case ABOUT_TO_EXPIRE = 'about_to_expire';

    /**
     * Sent once the certificate's expiry date has already passed.
     */
    case EXPIRED = 'expired';
}
