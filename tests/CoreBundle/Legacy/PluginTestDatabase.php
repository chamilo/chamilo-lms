<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use PDO;

/**
 * A plain PDO connection to the test database, for the DDL of plugin tables that cannot run inside a test (it breaks
 * the DAMA savepoint). Same database as Doctrine, which adds the dbname_suffix of the test environment (doctrine.yaml):
 * "_test" unless DATABASE_TEST_SUFFIX overrides it, the way a local setup with a differently named test database must
 * do for Doctrine too.
 */
final class PluginTestDatabase
{
    public static function pdo(): PDO
    {
        $dsn = \sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            $_SERVER['DATABASE_HOST'] ?? '127.0.0.1',
            $_SERVER['DATABASE_PORT'] ?? '3306',
            ($_SERVER['DATABASE_NAME'] ?? 'chamilo').($_SERVER['DATABASE_TEST_SUFFIX'] ?? '_test').($_SERVER['TEST_TOKEN'] ?? '')
        );

        return new PDO($dsn, $_SERVER['DATABASE_USER'] ?? 'root', $_SERVER['DATABASE_PASSWORD'] ?? '');
    }
}
