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
    // Active / Visible
    case ACTIVE = 'toggle-switch';
    // Inactive / Invisible
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
    // Shared setting (for multi-url) (previously an eye in a window)
    case SHARED_VISIBILITY = 'briefcase-eye';
    // Checkbox marked
    case CHECKBOX_MARKED = 'checkbox-marked-outline';
    // Checkbox empty
    case CHECKBOX_BLANK = 'checkbox-blank-outline';
    // Checkbox intermediate 1
    case CHECKBOX_INTERMEDIATE1 = 'checkbox-intermediate';
    // Checkbox intermediate 2
    case CHECKBOX_INTERMEDIATE2 = 'checkbox-intermediate-variant';
    // Checkbox intermediate 3
    case CHECKBOX_INTERMEDIATE3 = 'checkbox-blank-off-outline';
}
