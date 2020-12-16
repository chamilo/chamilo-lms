<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(true);
api_protect_teacher_script();

$request = HttpRequest::createFromGlobals();

$em = Database::getManager();

$toolLaunch = $em->find(
    ToolLaunch::class,
    $request->query->getInt('delete')
);

if (null === $toolLaunch
    || $toolLaunch->getCourse()->getId() !== api_get_course_entity()->getId()
) {
    api_not_allowed(true);
}

$plugin = XApiPlugin::create();

$em = Database::getManager();
$em->remove($toolLaunch);
$em->flush();

Display::addFlash(
    Display::return_message($plugin->get_lang('ActivityDeleted'), 'success')
);

header('Location: '.api_get_course_url());
exit;
