<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\XApiCmi5Item;
use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);
api_block_anonymous_users();

$request = Container::getRequest();
$em = Database::getManager();

/** @var XApiToolLaunch|null $toolLaunch */
$toolLaunch = $em->find(
    XApiToolLaunch::class,
    $request->query->getInt('id')
);

if (null === $toolLaunch || 'cmi5' !== $toolLaunch->getActivityType()) {
    header('Location: '.api_get_course_url());
    exit;
}

$plugin = XApiPlugin::create();
$course = api_get_course_entity();
$session = api_get_session_entity();
$cidReq = api_get_cidreq();

$interfaceLanguage = api_get_language_isocode();
if (empty($interfaceLanguage)) {
    $interfaceLanguage = 'en';
}

$itemsRepo = $em->getRepository(XApiCmi5Item::class);

$query = $itemsRepo->createQueryBuilder('item');
$query
    ->where($query->expr()->eq('item.tool', ':tool'))
    ->setParameter('tool', $toolLaunch)
;

$items = $query->getQuery()->getArrayResult();

$firstAuId = null;
foreach ($items as $item) {
    if (($item['type'] ?? null) === 'au') {
        $firstAuId = (int) $item['id'];
        break;
    }
}

$initialLaunchUrl = 'about:blank';
if (null !== $firstAuId) {
    $initialLaunchUrl = "launch.php?tool={$toolLaunch->getId()}&id={$firstAuId}&$cidReq";
}

$tocHtml = $itemsRepo->buildTree(
    $items,
    [
        'decorate' => true,
        'rootOpen' => '<ul class="space-y-2">',
        'rootClose' => '</ul>',
        'childOpen' => '<li class="space-y-2">',
        'childClose' => '</li>',
        'nodeDecorator' => function (array $node) use ($interfaceLanguage, $cidReq, $toolLaunch) {
            $title = '';

            if (!empty($node['title']) && is_array($node['title'])) {
                $title = XApiPlugin::extractVerbInLanguage($node['title'], $interfaceLanguage);
            }

            if (empty($title)) {
                $title = $node['identifier'] ?? get_lang('Item');
            }

            if (empty($title)) {
                $identifier = (string) ($node['identifier'] ?? '');

                if ('' !== $identifier) {
                    $path = (string) parse_url($identifier, PHP_URL_PATH);
                    $basename = trim(basename($path));

                    $title = '' !== $basename ? $basename : $identifier;
                }
            }

            if (empty($title)) {
                $title = get_lang('Item');
            }

            if ('block' === ($node['type'] ?? '')) {
                return Display::tag(
                    'div',
                    Security::remove_XSS($title),
                    ['class' => 'mt-4 mb-2 text-sm font-semibold text-gray-800']
                );
            }

            return Display::url(
                Security::remove_XSS($title),
                "launch.php?tool={$toolLaunch->getId()}&id={$node['id']}&$cidReq",
                [
                    'target' => 'ifr_content',
                    'class' => 'block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-primary',
                ]
            );
        },
    ]
);

$sessionId = $session ? (int) $session->getId() : 0;
$courseUrl = api_get_path(WEB_PATH).'course/'.$course->getId().'/home?sid='.$sessionId;
$pluginIndex = api_get_path(WEB_PLUGIN_PATH).'XApi/start.php?'.$cidReq;

$interbreadcrumb[] = [
    'url' => $pluginIndex,
    'name' => $plugin->get_lang('ToolTinCan'),
];

$view = new Template($toolLaunch->getTitle(), false, false, true, true, false);
$view->assign('tool', $toolLaunch);
$view->assign('toc_html', $tocHtml);
$view->assign('initial_launch_url', $initialLaunchUrl);
$view->assign('content', $view->fetch('XApi/views/cmi5_launch.twig'));
$view->display_no_layout_template();
