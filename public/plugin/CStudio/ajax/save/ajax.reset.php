<?php

declare(strict_types=1);
/**
 * This file contains the functions used by the OeL plugin.
 *
 * @version 18/05/2024
 */

require_once '../../0_dal/dal.global_lib.php';

require_once '../../inc/csrf_token.php';

require_once '../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

$iduser = (int) $VDB->w_api_get_user_id();
$cotk = 're'.uuidToken(30);

$table = 'plugin_oel_tools_token';
$VDB->update($table, ['token' => $cotk], ['id_user = ?' => $iduser]);
