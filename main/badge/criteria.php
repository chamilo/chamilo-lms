<?php
/* For licensing terms, see /license.txt */
/**
 * Show information about OpenBadge citeria
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.badge
 */
require_once '../inc/global.inc.php';

$entityManager = Database::getManager();
$skill = $entityManager->find('ChamiloCoreBundle:Skill', $_GET['id']);

$skillInfo = [
    'name' => $skill->getName(),
    'short_code' => $skill->getShortCode(),
    'description' => $skill->getDescription(),
    'criteria' => $skill->getCriteria(),
    'badge_image' => $skill->getWebIconPath()
];

$template = new Template();
$template->assign('skill_info', $skillInfo);

$content = $template->fetch(
    $template->get_template('skill/criteria.tpl')
);

$template->assign('content', $content);
$template->display_one_col_template();
