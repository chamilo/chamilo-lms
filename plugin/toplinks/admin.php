<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\PluginBundle\Entity\TopLinks\TopLink;
use Chamilo\PluginBundle\Entity\TopLinks\TopLinkRelTool;
use Chamilo\PluginBundle\TopLinks\Form\LinkForm as TopLinkForm;
use Symfony\Component\Filesystem\Filesystem;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = TopLinksPlugin::create();
$httpRequest = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

$pageBaseUrl = api_get_self();
$em = Database::getManager();
$linkRepo = $em->getRepository(TopLink::class);

$pageTitle = $plugin->get_title();
$pageActions = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    $pageBaseUrl
);
$pageContent = '';

$interbreadcrumb[] = [
    'name' => get_lang('Administration'),
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
];
$interbreadcrumb[] = ['name' => $plugin->get_title(), 'url' => $pageBaseUrl];

switch ($httpRequest->query->getAlpha('action', 'list')) {
    case 'list':
    default:
        array_pop($interbreadcrumb); // Only show link to administration in breadcrumb

        $pageActions = Display::url(
            Display::return_icon('add.png', get_lang('AddLink'), [], ICON_SIZE_MEDIUM),
            "$pageBaseUrl?".http_build_query(['action' => 'add'])
        );

        $table = new SortableTable(
            'toplinks',
            function () use ($linkRepo) {
                return $linkRepo->count([]);
            },
            function ($offset, $limit, $column, $direction) use ($linkRepo) {
                $links = $linkRepo->all($offset, $limit, $column, $direction);

                return array_map(
                    function (TopLink $link) {
                        return [
                            [$link->getTitle(), $link->getUrl()],
                            $link->getId(),
                        ];
                    },
                    $links
                );
            },
            0
        );
        $table->set_header(0, get_lang('LinkName'));
        $table->set_header(1, get_lang('Actions'), false, ['class' => 'th-head text-right'], ['class' => 'text-right']);
        $table->set_column_filter(
            0,
            function (array $params) {
                [$title, $url] = $params;

                return "$title<br>".Display::url($url, $url);
            }
        );
        $table->set_column_filter(
            1,
            function (int $id) use ($pageBaseUrl, $em, $plugin) {
                $missingCourses = $em->getRepository(TopLinkRelTool::class)->getMissingCoursesForTool($id);
                $countMissingCourses = count($missingCourses);

                $actions = [];
                $actions[] = Display::url(
                    Display::return_icon('edit.png', get_lang('Edit')),
                    "$pageBaseUrl?".http_build_query(['action' => 'edit', 'link' => $id])
                );

                if (count($missingCourses) > 0) {
                    $actions[] = Display::url(
                        Display::return_icon(
                            'view_tree.png',
                            sprintf($plugin->get_lang('ReplicateInXMissingCourses'), $countMissingCourses)
                        ),
                        "$pageBaseUrl?".http_build_query(['action' => 'replicate', 'link' => $id])
                    );
                } else {
                    $actions[] = Display::return_icon(
                        'view_tree_na.png',
                        $plugin->get_lang('AlreadyReplicatedInAllCourses')
                    );
                }

                $actions[] = Display::url(
                    Display::return_icon('delete.png', get_lang('Delete')),
                    "$pageBaseUrl?".http_build_query(['action' => 'delete', 'link' => $id])
                );

                return implode(PHP_EOL, $actions);
            }
        );

        if ($table->total_number_of_items) {
            $pageContent = $table->return_table();
        } else {
            $pageContent = Display::return_message(
                get_lang('NoData'),
                'info'
            );
        }
    break;
    case 'add':
        $pageTitle = get_lang('LinkAdd');

        $form = new TopLinkForm();
        $form->createElements();

        if ($form->validate()) {
            $values = $form->exportValues();

            $link = new TopLink();
            $link
                ->setTitle($values['title'])
                ->setUrl($values['url'])
                ->setTarget($values['target']);

            $em->persist($link);
            $em->flush();

            $iconPath = $form
                ->setLink($link)
                ->saveImage();

            $link->setIcon($iconPath);

            $em->flush();

            Display::addFlash(
                Display::return_message(get_lang('LinkAdded'), 'success')
            );

            header("Location: $pageBaseUrl");
            exit;
        }

        $pageContent = $form->returnForm();
        break;
    case 'edit':
        $pageTitle = get_lang('LinkMod');

        $link = $em->find(TopLink::class, $httpRequest->query->getInt('link'));

        if (null === $link) {
            Display::addFlash(
                Display::return_message(get_lang('NotFound'), 'error')
            );

            header("Location: $pageBaseUrl");
            exit;
        }

        $form = new TopLinkForm($link);
        $form->createElements();

        if ($form->validate()) {
            $values = $form->exportValues();

            $iconPath = $form->saveImage();

            $link
                ->setTitle($values['title'])
                ->setUrl($values['url'])
                ->setIcon($iconPath)
                ->setTarget($values['target']);

            $em->flush();

            $em->getRepository(TopLinkRelTool::class)->updateTools($link);

            Display::addFlash(
                Display::return_message(get_lang('LinkModded'), 'success')
            );

            header("Location: $pageBaseUrl");
            exit;
        }

        $pageContent = $form->returnForm();
        break;
    case 'delete':
        $link = $em->find(TopLink::class, $httpRequest->query->getInt('link'));

        if (null === $link) {
            Display::addFlash(
                Display::return_message(get_lang('NotFound'), 'error')
            );

            header("Location: $pageBaseUrl");
            exit;
        }

        if ($link->getIcon()) {
            $fullIconPath = api_get_path(SYS_UPLOAD_PATH).'plugins/toplinks/'.$link->getIcon();

            $fs = new Filesystem();
            $fs->remove($fullIconPath);
        }

        $em->remove($link);
        $em->flush();

        Display::addFlash(
            Display::return_message(get_lang('LinkDeleted'), 'success')
        );

        header("Location: $pageBaseUrl");
        exit;
    case 'replicate':
        $link = $em->find(TopLink::class, $httpRequest->query->getInt('link'));

        if (null === $link) {
            Display::addFlash(
                Display::return_message(get_lang('NotFound'), 'error')
            );

            header("Location: $pageBaseUrl");
            exit;
        }

        $missingCourses = $em->getRepository(TopLinkRelTool::class)->getMissingCoursesForTool($link->getId());

        /** @var CourseEntity $missingCourse */
        foreach ($missingCourses as $missingCourse) {
            $plugin->addToolInCourse($missingCourse->getId(), $link);
        }

        Display::addFlash(
            Display::return_message($plugin->get_lang('LinkReplicated'), 'success')
        );

        header("Location: $pageBaseUrl");
        exit;
}

$view = new Template($plugin->get_title());
$view->assign('header', $pageTitle);
$view->assign('actions', Display::toolbarAction('xapi_actions', [$pageActions]));
$view->assign('content', $pageContent);
$view->display_one_col_template();
