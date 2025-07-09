<?php

/* For licensing terms, see /license.txt */
/**
 * This script lists the necessary variables that allow the installation
 * system to know in which version is the current Chamilo install. This
 * script should be overwritten with each upgrade of Chamilo. It is not
 * required from any other process of Chamilo than the installation or upgrade.
 * It also helps for automatic packaging of unstable versions.
 */

return [
    'new_version' => '2.0.0',
    'new_version_status' => 'alpha',
    'new_version_last_id' => '3',
    'new_version_stable' => false,
    'new_version_major' => true,
    'software_name' => 'Chamilo',
    'software_url' => 'https://chamilo.org/',
];
