<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

/**
 * When gid is present in the request, restrict resource collections/items to the group context.
 * - gid > 0  => only resources linked to that group
 * - gid = 0  => exclude resources linked to any group.
 */
interface ResourceRestrictToGroupContextInterface {}
