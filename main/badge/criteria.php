<?php
/* For licensing terms, see /license.txt */
/**
 * Show information about OpenBadge citeria
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.badge
 */
header('Content-Type: text/plain');

require_once '../inc/global.inc.php';

$skillId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$objSkill = new Skill();
$skill = $objSkill->get($skillId);

echo $skill['criteria'];
