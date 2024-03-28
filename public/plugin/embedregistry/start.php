<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\EmbedRegistry\Embed;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script(true);

$plugin = EmbedRegistryPlugin::create();

if ('false' === $plugin->get(EmbedRegistryPlugin::SETTING_ENABLED)) {
    api_not_allowed(true);
}

$isAllowedToEdit = api_is_allowed_to_edit(true);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

$em = Database::getManager();
$embedRepo = $em->getRepository('ChamiloPluginBundle:EmbedRegistry\Embed');

$course = api_get_course_entity(api_get_course_int_id());
$session = api_get_session_entity(api_get_session_id());

$actions = [];

$view = new Template($plugin->getToolTitle());
$view->assign('is_allowed_to_edit', $isAllowedToEdit);

switch ($action) {
    case 'add':
        if (!$isAllowedToEdit) {
            api_not_allowed(true);
        }

        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            api_get_self()
        );

        $form = new FormValidator('frm_edit');
        $form->addText(
            'title',
            [get_lang('Title'), $plugin->get_lang('EmbedTitleHelp')],
            true
        );
        $form->addDateRangePicker(
            'range',
            [get_lang('DateRange'), $plugin->get_lang('EmbedDateRangeHelp')]
        );
        $form->addTextarea(
            'html_code',
            [$plugin->get_lang('HtmlCode'), $plugin->get_lang('HtmlCodeHelp')],
            ['rows' => 5],
            true
        );
        $form->addButtonUpdate(get_lang('Add'));
        $form->addHidden('action', 'add');

        if ($form->validate()) {
            $values = $form->exportValues();

            $startDate = api_get_utc_datetime($values['range_start'], false, true);
            $endDate = api_get_utc_datetime($values['range_end'], false, true);

            $embed = new Embed();
            $embed
                ->setTitle($values['title'])
                ->setDisplayStartDate($startDate)
                ->setDisplayEndDate($endDate)
                ->setHtmlCode($values['html_code'])
                ->setCourse($course)
                ->setSession($session);

            $em->persist($embed);
            $em->flush();

            Display::addFlash(
                Display::return_message(get_lang('Added'), 'success')
            );

            header('Location: '.api_get_self());
            exit;
        }

        $view->assign('header', $plugin->get_lang('CreateEmbeddable'));
        $view->assign('form', $form->returnForm());

        $externalUrl = $plugin->get(EmbedRegistryPlugin::SETTING_EXTERNAL_URL);

        if (!empty($externalUrl)) {
            $view->assign('external_url', $externalUrl);
        }
        break;
    case 'edit':
        if (!$isAllowedToEdit) {
            api_not_allowed(true);
        }

        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            api_get_self()
        );

        $embedId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        if (!$embedId) {
            break;
        }

        /** @var Embed|null $embed */
        $embed = $embedRepo->find($embedId);

        if (!$embed) {
            Display::addFlash(Display::return_message($plugin->get_lang('ContentNotFound'), 'danger'));

            break;
        }

        $form = new FormValidator('frm_edit');
        $form->addText('title', get_lang('Title'), true);
        $form->addDateRangePicker('range', get_lang('DateRange'));
        $form->addTextarea('html_code', $plugin->get_lang('HtmlCode'), ['rows' => 5], true);
        $form->addButtonUpdate(get_lang('Edit'));
        $form->addHidden('id', $embed->getId());
        $form->addHidden('action', 'edit');

        if ($form->validate()) {
            $values = $form->exportValues();

            $startDate = api_get_utc_datetime($values['range_start'], false, true);
            $endDate = api_get_utc_datetime($values['range_end'], false, true);

            $embed
                ->setTitle($values['title'])
                ->setDisplayStartDate($startDate)
                ->setDisplayEndDate($endDate)
                ->setHtmlCode($values['html_code']);

            $em->persist($embed);
            $em->flush();

            Display::addFlash(
                Display::return_message(get_lang('Updated'), 'success')
            );

            header('Location: '.api_get_self());
            exit;
        }

        $form->setDefaults(
            [
                'title' => $embed->getTitle(),
                'range' => api_get_local_time($embed->getDisplayStartDate())
                    .' / '
                    .api_get_local_time($embed->getDisplayEndDate()),
                'html_code' => $embed->getHtmlCode(),
            ]
        );

        $view->assign('header', $plugin->get_lang('EditEmbeddable'));
        $view->assign('form', $form->returnForm());
        break;
    case 'delete':
        $embedId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        if (!$embedId) {
            break;
        }

        /** @var Embed|null $embed */
        $embed = $embedRepo->find($embedId);

        if (!$embed) {
            Display::addFlash(Display::return_message($plugin->get_lang('ContentNotFound'), 'danger'));

            break;
        }

        $em->remove($embed);
        $em->flush();

        Display::addFlash(
            Display::return_message(get_lang('Deleted'), 'success')
        );

        header('Location: '.api_get_self());
        exit;
    default:
        $currentEmbed = $plugin->getCurrentEmbed($course, $session);

        /** @var array|Embed[] $embeds */
        $embeds = $embedRepo->findBy(['course' => $course, 'session' => $session]);

        $tableData = [];

        foreach ($embeds as $embed) {
            $data = [
                $embed->getTitle(),
                api_convert_and_format_date($embed->getDisplayStartDate()),
                api_convert_and_format_date($embed->getDisplayEndDate()),
                $embed,
            ];

            if ($isAllowedToEdit) {
                $data[] = $embed;
            }

            $tableData[] = $data;
        }

        if ($isAllowedToEdit) {
            $btnAdd = Display::toolbarButton(
                $plugin->get_lang('CreateEmbeddable'),
                api_get_self().'?action=add',
                'file-code-o',
                'primary'
            );

            $view->assign(
                'actions',
                Display::toolbarAction($plugin->get_name(), [$btnAdd])
            );

            if (in_array($action, ['add', 'edit'])) {
                $view->assign('form', $form->returnForm());
            }
        }

        if ($currentEmbed) {
            $view->assign('current_embed', $currentEmbed);
            $view->assign(
                'current_link',
                Display::toolbarButton(
                    $plugin->get_lang('LaunchContent'),
                    $plugin->getViewUrl($embed),
                    'rocket',
                    'info'
                )
            );
        }

        $table = new SortableTableFromArray($tableData, 1);
        $table->set_header(0, get_lang('Title'));
        $table->set_header(1, get_lang('AvailableFrom'), true, 'th-header text-center', ['class' => 'text-center']);
        $table->set_header(2, get_lang('AvailableTill'), true, 'th-header text-center', ['class' => 'text-center']);

        if ($isAllowedToEdit) {
            $table->set_header(3, get_lang('Members'), false, 'th-header text-right', ['class' => 'text-right']);
            $table->set_column_filter(
                3,
                function (Embed $value) use ($plugin) {
                    return $plugin->getMembersCount($value);
                }
            );
        }

        $table->set_header(
            $isAllowedToEdit ? 4 : 3,
            get_lang('Actions'),
            false,
            'th-header text-right',
            ['class' => 'text-right']
        );
        $table->set_column_filter(
            $isAllowedToEdit ? 4 : 3,
            function (Embed $value) use ($isAllowedToEdit, $plugin) {
                $actions = [];

                $actions[] = Display::url(
                    Display::return_icon('external_link.png', get_lang('View')),
                    $plugin->getViewUrl($value)
                );

                if ($isAllowedToEdit) {
                    $actions[] = Display::url(
                        Display::return_icon('edit.png', get_lang('Edit')),
                        api_get_self().'?action=edit&id='.$value->getId()
                    );

                    $actions[] = Display::url(
                        Display::return_icon('delete.png', get_lang('Delete')),
                        api_get_self().'?action=delete&id='.$value->getId()
                    );
                }

                return implode(PHP_EOL, $actions);
            }
        );

        $view->assign('embeds', $embeds);
        $view->assign('table', $table->return_table());
}

$content = $view->fetch('embedregistry/view/start.tpl');

if ($actions) {
    $actions = implode(PHP_EOL, $actions);

    $view->assign(
        'actions',
        Display::toolbarAction($plugin->get_name(), [$actions])
    );
}

$view->assign('content', $content);
$view->display_one_col_template();
