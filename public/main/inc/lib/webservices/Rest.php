<?php

/* For licensing terms, see /license.txt */

/**
 * This file has been intentionally blanked.
 *
 * It used to hold the "REST" web service of Chamilo 1.x (main/webservices/api/v2.php), which was removed
 * from Chamilo 2 (new web services go through API Platform) while its implementation stayed here, without
 * any entry point, and without the authorization checks that were added later in Chamilo 1.11.x
 * (course read/write access, class management, course self-enrolment).
 *
 * Only the constant that is still used by the push notifications (see Notification) is kept.
 * Do not restore the old implementation: expose what is needed through API Platform instead.
 */
final class Rest
{
    public const EXTRA_FIELD_GCM_REGISTRATION = 'gcm_registration_id';
}
