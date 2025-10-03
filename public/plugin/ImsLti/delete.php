<?php
/* For license terms, see /license.txt */

use Chamilo\LtiBundle\Entity\ExternalTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = ImsLtiPlugin::create();

api_protect_admin_script();

$em = Database::getManager();

/** @var ExternalTool|null $tool */
$tool = isset($_GET['id']) ? $em->find(ExternalTool::class, intval($_GET['id'])) : 0;

if (!$tool) {
    api_not_allowed(true);
}

$links = [];
$links[] = 'ImsLti/start.php?id='.$tool->getId();

if (!$tool->getParent()) {
    foreach ($tool->getChildren() as $child) {
        $links[] = "ImsLti/start.php?id=".$child->getId();
    }
}

$em->remove($tool);
$em->flush();

$em
    ->createQuery("DELETE FROM ChamiloCourseBundle:CTool ct WHERE ct.category = :category AND ct.link IN (:links)")
    ->execute(['category' => 'plugin', 'links' => $links]);

Display::addFlash(
    Display::return_message($plugin->get_lang('ToolDeleted'), 'success')
);

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'ImsLti/admin.php');
exit;
