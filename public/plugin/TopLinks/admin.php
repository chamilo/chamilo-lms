<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\TopLinks\Entity\TopLink;
use Chamilo\PluginBundle\TopLinks\Form\LinkForm as TopLinkForm;
use Symfony\Component\HttpFoundation\RedirectResponse;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = TopLinksPlugin::create();
$plugin->ensureSchema();
$httpRequest = Container::getRequest();

$pageBaseUrl = api_get_self();
$em = Database::getManager();
$linkRepo = $em->getRepository(TopLink::class);

$pageTitle = $plugin->get_title();
$pageContent = '';

$pluginsUrl = api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins&plugin_tab=all';

$interbreadcrumb[] = [
    'name' => get_lang('Administration'),
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
];
$interbreadcrumb[] = ['name' => get_lang('Plugins'), 'url' => $pluginsUrl];
$interbreadcrumb[] = ['name' => $plugin->get_title(), 'url' => $pageBaseUrl];

function toplinks_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function toplinks_lang(string $key): string
{
    static $fallbacks = [
        'AddLink' => 'Add link',
        'Links' => 'Links',
        'ManageTopLinks' => 'Manage the TopLinks shortcuts displayed on the course home.',
        'CreateTopLinksEmpty' => 'Create links that will be added as shortcuts in every course.',
        'CreateOrUpdateTopLinkHelp' => 'Create or update a link. Internal paths must start with /. External URLs must start with http:// or https://.',
        'TopLinksSectionTitle' => 'Top Links',
        'TopLinksSectionDescription' => 'Create shortcuts in all courses and position them on the course home.',
        'NoData' => 'No data',
        'AlreadyReplicatedInAllCourses' => 'Available in all courses',
        'ReplicateInXMissingCourses' => 'Create shortcut in %d missing courses',
        'LinkReplicated' => 'Shortcut created in courses',
    ];

    $translated = TopLinksPlugin::create()->get_lang($key);

    if ('' !== trim((string) $translated) && $translated !== $key) {
        return $translated;
    }

    $globalTranslation = get_lang($key);
    if ('' !== trim((string) $globalTranslation) && $globalTranslation !== $key) {
        return $globalTranslation;
    }

    return $fallbacks[$key] ?? $key;
}

function toplinks_redirect(string $url): void
{
    (new RedirectResponse($url))->send();
    exit;
}

function toplinks_action_url(string $baseUrl, array $params): string
{
    return $baseUrl.'?'.http_build_query($params);
}

function toplinks_header(string $title, string $description, string $backUrl): string
{
    return '
        <section class="mb-6 rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="mb-2 text-caption font-semibold uppercase tracking-wide text-primary">'.toplinks_escape(get_lang('Plugin')).'</p>
                    <h2 class="mb-2 text-2xl font-bold text-gray-90">'.toplinks_escape($title).'</h2>
                    <p class="max-w-3xl text-body-2 text-gray-50">'.toplinks_escape($description).'</p>
                </div>
                <a class="inline-flex items-center justify-center rounded-lg border border-gray-25 bg-white px-4 py-2 text-body-2 font-semibold text-gray-90 hover:border-primary hover:text-primary"
                   href="'.toplinks_escape($backUrl).'">
                    <span class="mdi mdi-arrow-left ch-tool-icon mr-2"></span>'.toplinks_escape(get_lang('Back')).'
                </a>
            </div>
        </section>
    ';
}

function toplinks_empty_state(string $addUrl): string
{
    return '
        <section class="rounded-2xl border border-gray-25 bg-white p-8 text-center shadow-sm">
            <span class="mdi mdi-link-variant ch-tool-icon-gradient mb-3 inline-block text-5xl"></span>
            <h3 class="mb-2 text-xl font-bold text-gray-90">'.toplinks_escape(toplinks_lang('NoData')).'</h3>
            <p class="mb-5 text-body-2 text-gray-50">'.toplinks_escape(toplinks_lang('CreateTopLinksEmpty')).'</p>
            <a class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-body-2 font-semibold !text-white hover:opacity-90"
               href="'.toplinks_escape($addUrl).'">
                <span class="mdi mdi-plus-box mr-2"></span>'.toplinks_escape(toplinks_lang('AddLink')).'
            </a>
        </section>
    ';
}

function toplinks_render_list(array $links, string $pageBaseUrl, TopLinksPlugin $plugin): string
{
    $addUrl = toplinks_action_url($pageBaseUrl, ['action' => 'add']);
    $securityToken = Security::get_token();
    $html = '
        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-90">'.toplinks_escape(toplinks_lang('Links')).'</h3>
                <p class="text-body-2 text-gray-50">'.toplinks_escape(toplinks_lang('ManageTopLinks')).'</p>
            </div>
            <a class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-body-2 font-semibold !text-white hover:opacity-90"
               href="'.toplinks_escape($addUrl).'">
                <span class="mdi mdi-plus-box mr-2"></span>'.toplinks_escape(toplinks_lang('AddLink')).'
            </a>
        </div>
    ';

    if ([] === $links) {
        return $html.toplinks_empty_state($addUrl);
    }

    $html .= '<div class="grid gap-4">';

    /** @var TopLink $link */
    foreach ($links as $link) {
        $missingCourses = $plugin->getMissingCoursesForLink($link);
        $countMissingCourses = count($missingCourses);
        $editUrl = toplinks_action_url($pageBaseUrl, ['action' => 'edit', 'link' => $link->getId()]);
        $replicateUrl = toplinks_action_url($pageBaseUrl, [
            'action' => 'replicate',
            'link' => $link->getId(),
            'sec_token' => $securityToken,
        ]);
        $deleteUrl = toplinks_action_url($pageBaseUrl, [
            'action' => 'delete',
            'link' => $link->getId(),
            'sec_token' => $securityToken,
        ]);

        $icon = $link->getIcon()
            ? '<img class="h-12 w-12 rounded-xl border border-gray-25 object-cover" src="'.toplinks_escape($plugin->getIconUrl($link->getIcon())).'" alt="">'
            : '<div class="flex h-12 w-12 items-center justify-center rounded-xl bg-support-1 text-primary"><span class="mdi mdi-link-variant text-2xl"></span></div>';

        $replicateAction = '';
        if (0 < $countMissingCourses) {
            $replicateAction = '
                <a class="inline-flex items-center rounded-lg border border-info px-3 py-2 text-caption font-semibold text-info hover:bg-support-1"
                   href="'.toplinks_escape($replicateUrl).'">
                    <span class="mdi mdi-content-copy mr-2"></span>'.
                    toplinks_escape(sprintf(toplinks_lang('ReplicateInXMissingCourses'), $countMissingCourses)).'
                </a>
            ';
        } else {
            $replicateAction = '
                <span class="inline-flex items-center rounded-lg border border-gray-25 bg-gray-15 px-3 py-2 text-caption font-semibold text-gray-50">
                    <span class="mdi mdi-check-circle mr-2"></span>'.toplinks_escape(toplinks_lang('AlreadyReplicatedInAllCourses')).'
                </span>
            ';
        }

        $html .= '
            <article class="rounded-2xl border border-gray-25 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex min-w-0 gap-4">
                        '.$icon.'
                        <div class="min-w-0">
                            <h4 class="mb-1 truncate text-lg font-bold text-gray-90">'.toplinks_escape($link->getTitle()).'</h4>
                            <a class="break-all text-body-2 font-medium text-primary hover:underline"
                               href="'.toplinks_escape($link->getUrl()).'"
                               target="'.toplinks_escape($link->getTarget()).'"
                               rel="noopener noreferrer">'.toplinks_escape($link->getUrl()).'</a>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span class="rounded-full bg-support-1 px-3 py-1 text-caption font-semibold text-primary">'.toplinks_escape($link->getTarget()).'</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 md:justify-end">
                        <a class="inline-flex items-center rounded-lg bg-secondary px-3 py-2 text-caption font-semibold !text-secondary-button-text hover:opacity-90"
                           href="'.toplinks_escape($editUrl).'">
                            <span class="mdi mdi-pencil mr-2"></span>'.toplinks_escape(get_lang('Edit')).'
                        </a>
                        '.$replicateAction.'
                        <a class="inline-flex items-center rounded-lg bg-danger px-3 py-2 text-caption font-semibold !text-danger-button-text hover:opacity-90"
                           href="'.toplinks_escape($deleteUrl).'"
                           onclick="return confirm(\''.toplinks_escape(get_lang('Please confirm your choice')).'\');">
                            <span class="mdi mdi-delete mr-2"></span>'.toplinks_escape(get_lang('Delete')).'
                        </a>
                    </div>
                </div>
            </article>
        ';
    }

    return $html.'</div>';
}

function toplinks_render_form(string $formHtml): string
{
    return '
        <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
            <div class="mb-5 rounded-xl border border-info bg-support-1 p-4 text-body-2 text-gray-90">
                '.toplinks_escape(toplinks_lang('CreateOrUpdateTopLinkHelp')).'
            </div>
            '.$formHtml.'
        </section>
    ';
}

$action = $httpRequest->query->getAlpha('action', 'list');

switch ($action) {
    case 'add':
        $pageTitle = toplinks_lang('LinkAdd');

        $form = new TopLinkForm();
        $form->createElements();

        if ($form->validate()) {
            $values = $form->exportValues();

            $link = new TopLink();
            $link
                ->setTitle((string) $values['title'])
                ->setUrl((string) $values['url'])
                ->setTarget((string) $values['target']);

            $em->persist($link);
            $em->flush();

            $iconPath = $form
                ->setLink($link)
                ->saveImage();

            $link->setIcon($iconPath);

            $em->flush();

            $plugin->addShortcutInAllCourses($link);

            Display::addFlash(
                Display::return_message(get_lang('LinkAdded'), 'success')
            );

            toplinks_redirect($pageBaseUrl);
        }

        $pageContent = toplinks_render_form($form->returnForm());
        break;

    case 'edit':
        $pageTitle = toplinks_lang('LinkMod');

        $link = $em->find(TopLink::class, $httpRequest->query->getInt('link'));

        if (null === $link) {
            Display::addFlash(
                Display::return_message(get_lang('Resource not found'), 'error')
            );

            toplinks_redirect($pageBaseUrl);
        }

        $form = new TopLinkForm($link);
        $form->createElements();

        if ($form->validate()) {
            $values = $form->exportValues();

            $iconPath = $form->saveImage();

            $link
                ->setTitle((string) $values['title'])
                ->setUrl((string) $values['url'])
                ->setIcon($iconPath)
                ->setTarget((string) $values['target']);

            $em->flush();

            $plugin->updateShortcutsForLink($link);

            Display::addFlash(
                Display::return_message(get_lang('Item updated'), 'success')
            );

            toplinks_redirect($pageBaseUrl);
        }

        $pageContent = toplinks_render_form($form->returnForm());
        break;

    case 'delete':
        if (!Security::check_token('get')) {
            Display::addFlash(
                Display::return_message(get_lang('Invalid security token'), 'error')
            );

            toplinks_redirect($pageBaseUrl);
        }

        $link = $em->find(TopLink::class, $httpRequest->query->getInt('link'));

        if (null === $link) {
            Display::addFlash(
                Display::return_message(get_lang('Resource not found'), 'error')
            );

            toplinks_redirect($pageBaseUrl);
        }

        $plugin->deleteShortcutsForLink($link);

        if ($link->getIcon()) {
            $plugin->deleteIcon($link->getIcon());
        }

        $em->remove($link);
        $em->flush();

        Display::addFlash(
            Display::return_message(get_lang('Item deleted'), 'success')
        );

        toplinks_redirect($pageBaseUrl);

    case 'replicate':
        if (!Security::check_token('get')) {
            Display::addFlash(
                Display::return_message(get_lang('Invalid security token'), 'error')
            );

            toplinks_redirect($pageBaseUrl);
        }

        $link = $em->find(TopLink::class, $httpRequest->query->getInt('link'));

        if (null === $link) {
            Display::addFlash(
                Display::return_message(get_lang('Resource not found'), 'error')
            );

            toplinks_redirect($pageBaseUrl);
        }

        $missingCourses = $plugin->getMissingCoursesForLink($link);

        foreach ($missingCourses as $missingCourse) {
            $plugin->addShortcutInCourse($missingCourse, $link);
        }

        Display::addFlash(
            Display::return_message(toplinks_lang('LinkReplicated'), 'success')
        );

        toplinks_redirect($pageBaseUrl);

    case 'list':
    default:
        array_pop($interbreadcrumb);
        $links = $linkRepo->findBy([], ['title' => 'ASC']);

        $pageContent = toplinks_render_list($links, $pageBaseUrl, $plugin);
        break;
}

$pageDescription = $plugin->get_lang('plugin_comment');
$pageHeader = toplinks_header($pageTitle, $pageDescription, $pluginsUrl);

$view = new Template($plugin->get_title());
$view->assign('header', $pageTitle);
$view->assign('actions', '');
$view->assign('content', $pageHeader.$pageContent);
$view->display_one_col_template();
