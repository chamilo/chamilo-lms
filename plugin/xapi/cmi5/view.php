<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\Cmi5Item;
use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Xabbuh\XApi\Model\LanguageMap;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);
api_block_anonymous_users();

$request = HttpRequest::createFromGlobals();

$em = Database::getManager();

$toolLaunch = $em->find(
    ToolLaunch::class,
    $request->query->getInt('id')
);

if (null === $toolLaunch
    || 'cmi5' !== $toolLaunch->getActivityType()
) {
    header('Location: '.api_get_course_url());
    exit;
}

$plugin = XApiPlugin::create();
$course = api_get_course_entity();
$session = api_get_session_entity();
$cidReq = api_get_cidreq();
$user = api_get_user_entity(api_get_user_id());
$interfaceLanguage = api_get_interface_language();

$itemsRepo = $em->getRepository(Cmi5Item::class);

$query = $em->createQueryBuilder()
    ->select('item')
    ->from(Cmi5Item::class, 'item')
    ->where('item.tool = :tool')
    ->setParameter('tool', $toolLaunch->getId())
    ->getQuery();

$tocHtml = $itemsRepo->buildTree(
    $query->getArrayResult(),
    [
        'decorate' => true,
        'rootOpen' => '<ul>',
        'rootClose' => '</ul>',
        'childOpen' => '<li>',
        'childClose' => '</li>',
        'nodeDecorator' => function ($node) use ($interfaceLanguage, $cidReq, $toolLaunch) {
            $titleMap = LanguageMap::create($node['title']);
            $title = XApiPlugin::extractVerbInLanguage($titleMap, $interfaceLanguage);

            if ('block' === $node['type']) {
                return Display::page_subheader($title, null, 'h4');
            }

            return Display::url(
                $title,
                "launch.php?tool={$toolLaunch->getId()}&id={$node['id']}&$cidReq",
                [
                    'target' => 'ifr_content',
                    'class' => 'text-left btn-link',
                ]
            );
        },
    ]
);

$webPluginPath = api_get_path(WEB_PLUGIN_PATH);

$htmlHeadXtra[] = api_get_css($webPluginPath.'xapi/assets/css/cmi5_launch.css');
$htmlHeadXtra[] = api_get_js_simple($webPluginPath.'xapi/assets/js/cmi5_launch.js');

$view = new Template('', false, false, true, true, false);
$view->assign('tool', $toolLaunch);
$view->assign('toc_html', $tocHtml);
$view->assign('content', $view->fetch('xapi/views/cmi5_launch.twig'));
$view->display_no_layout_template();
