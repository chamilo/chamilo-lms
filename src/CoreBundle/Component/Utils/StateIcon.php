<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Component\Utils;

enum StateIcon: string
{
    // Public
    case PUBLIC_VISIBILITY = 'eye';
    // Open
    case OPEN_VISIBILITY = 'eye-outline';
    // Private
    case PRIVATE_VISIBILITY = 'eye-off';
    // Closed
    case CLOSED_VISIBILITY = 'eye-off-outline';
    // Hidden
    case HIDDEN_VISIBILITY = 'eye-closed';
    // Active
    case ACTIVE = 'toggle-switch';
    // Inactive
    case INACTIVE = 'toggle-switch-off';
    // Expired
    case EXPIRED = 'timer-alert-outline';
    // Error
    case ERROR = 'alert-circle';
    // Warning
    case WARNING = 'alert';
    // Complete/Success
    case COMPLETE = 'check-circle';
    // Incomplete/Failure
    case INCOMPLETE = 'close-circle';
    // Notification/Alert by mail
    case MAIL_NOTIFICATION = 'email-alert';
}
