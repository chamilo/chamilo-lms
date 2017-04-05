<?php
/* For licensing terms, see /license.txt */

/**
 * Show information about the OpenBadge class
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.badge
 */
header('Content-Type: application/json');

require_once __DIR__.'/../inc/global.inc.php';

$skillId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$objSkill = new Skill();
$skill = $objSkill->get($skillId);

$json = array(
    'name' => $skill['name'],
    'description' => $skill['description'],
    'image' => api_get_path(WEB_UPLOAD_PATH)."badges/{$skill['icon']}",
    'criteria' => api_get_path(WEB_CODE_PATH)."badge/criteria.php?id=$skillId",
    'issuer' => api_get_path(WEB_CODE_PATH)."badge/issuer.php",
);

echo json_encode($json);
