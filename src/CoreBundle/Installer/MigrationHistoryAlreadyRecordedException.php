<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Installer;

use RuntimeException;

/**
 * Raised when the migration history already holds executed migrations, so recording it
 * again would flag migrations that never ran.
 */
final class MigrationHistoryAlreadyRecordedException extends RuntimeException {}
