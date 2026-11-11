<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use RuntimeException;

/**
 * A merge that the rules forbid (privilege loss, teacher into student, actor out of scope). The message is safe to
 * show to the administrator who asked for it.
 */
final class UserMergeRefusedException extends RuntimeException {}
