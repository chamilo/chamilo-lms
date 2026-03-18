<?php
/* For license terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Chamilo\LtiBundle\Entity\ExternalTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = ImsLtiPlugin::create();

api_protect_admin_script();

$em = Database::getManager();

/** @var ExternalTool|null $tool */
$tool = isset($_GET['id']) ? $em->find(ExternalTool::class, (int) $_GET['id']) : null;

if (!$tool) {
    api_not_allowed(true);
}

/** @var CShortcutRepository $shortcutRepository */
$shortcutRepository = $em->getRepository(CShortcut::class);

$connection = $em->getConnection();
$connection->beginTransaction();

try {
    $shortcuts = $shortcutRepository->getShortcutsFromResource($tool);

    foreach ($shortcuts as $shortcut) {
        $em->remove($shortcut);
    }

    $em->flush();

    $em->remove($tool);
    $em->flush();

    $connection->commit();
} catch (Throwable $exception) {
    $connection->rollBack();

    throw $exception;
}

Display::addFlash(
    Display::return_message($plugin->get_lang('ToolDeleted'), 'success')
);

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'ImsLti/admin.php');
exit;
