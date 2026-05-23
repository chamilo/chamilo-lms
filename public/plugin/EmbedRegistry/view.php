<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\EmbedRegistry\Entity\Embed;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script(true);

function embed_registry_view_is_same_context(Embed $embed, Course $course, ?Session $session): bool
{
    if ($course->getId() !== $embed->getCourse()->getId()) {
        return false;
    }

    $embedSession = $embed->getSession();

    if (null === $session && null === $embedSession) {
        return true;
    }

    if (null === $session || null === $embedSession) {
        return false;
    }

    return $session->getId() === $embedSession->getId();
}

$plugin = EmbedRegistryPlugin::create();

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$em = Database::getManager();
$embedRepo = $em->getRepository(Embed::class);

$course = api_get_course_entity(api_get_course_int_id());
$session = api_get_session_entity(api_get_session_id());

$embedId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

if (!$embedId) {
    api_not_allowed(true);
}

/** @var Embed|null $embed */
$embed = $embedRepo->find($embedId);

if (!$embed) {
    api_not_allowed(
        true,
        Display::return_message($plugin->get_lang('ContentNotFound'), 'danger')
    );
}

if (!embed_registry_view_is_same_context($embed, $course, $session)) {
    api_not_allowed(true);
}

$plugin->saveEventAccessTool();

$interbreadcrumb[] = [
    'name' => $plugin->getToolTitle(),
    'url' => api_get_path(WEB_PLUGIN_PATH).$plugin->get_name().'/start.php?'.api_get_cidreq(),
];

$actions = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH).$plugin->get_name().'/start.php?'.api_get_cidreq()
);

$view = new Template($embed->getTitle());
$view->assign('header', $embed->getTitle());
$view->assign('actions', Display::toolbarAction($plugin->get_name(), [$actions]));
$view->assign(
    'content',
    '<p>'.$plugin->formatDisplayDate($embed).'</p>'
        .PHP_EOL
        .Security::remove_XSS($embed->getHtmlCode(), COURSEMANAGERLOWSECURITY)
);
$view->display_one_col_template();
