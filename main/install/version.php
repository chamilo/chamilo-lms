<?php
/* For licensing terms, see /license.txt */
/**
 * This script lists the necessary variables that allow the installation
 * system to know in which version is the current Chamilo install. This
 * script should be overwritten with each upgrade of Chamilo. It is not
 * required from any other process of Chamilo than the installation or upgrade.
 * It also helps for automatic packaging of unstable versions.
 *
 * @package chamilo.install
 */

return array(
    'version' => '10.0.0',
    'version_status' => 'Unstable',
    'version_last_id' => 2,
    'version_stable' => false,
    'version_major' => true,
    'software_name' => 'Chamilo',
    'software_url' => 'http://www.chamilo.org/'
);
