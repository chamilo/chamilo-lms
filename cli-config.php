<?php
/* For licensing terms, see /license.txt */

/**
 * Script needed to execute bin/doctrine.php in the command line
 * in order to:
 *
 * - Generate migrations
 * - Create schema
 * - Update schema
 * - Validate schema
 * - Etc
 **/

use Doctrine\ORM\Tools\Console\ConsoleRunner;

require_once 'main/inc/global.inc.php';

// replace with mechanism to retrieve EntityManager in your app
$entityManager = Database::getManager();

return ConsoleRunner::createHelperSet($entityManager);

