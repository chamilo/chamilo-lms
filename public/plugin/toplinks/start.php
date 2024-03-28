<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\TopLinks\TopLink;
use Symfony\Component\HttpFoundation\RedirectResponse;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(true);
api_block_anonymous_users();

$plugin = TopLinksPlugin::create();
$httpRequest = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

$em = Database::getManager();

$link = $em->find(TopLink::class, $httpRequest->query->getInt('link'));

if (null === $link) {
    Display::addFlash(
        Display::return_message(get_lang('NotFound'), 'error')
    );

    RedirectResponse::create(api_get_course_url())->send();
    exit;
}

RedirectResponse::create($link->getUrl())->send();
