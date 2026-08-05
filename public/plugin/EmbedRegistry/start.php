<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\EmbedRegistry\Entity\Embed;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script(true);

function embed_registry_get_url(array $params = []): string
{
    $query = api_get_cidreq();

    foreach ($params as $key => $value) {
        $query .= (empty($query) ? '' : '&').urlencode((string) $key).'='.urlencode((string) $value);
    }

    return api_get_self().(empty($query) ? '' : '?'.$query);
}

function embed_registry_to_datetime($value): \DateTime
{
    if ($value instanceof \DateTime) {
        return $value;
    }

    if ($value instanceof \DateTimeInterface) {
        $date = new \DateTime('@'.$value->getTimestamp());
        $date->setTimezone(new \DateTimeZone('UTC'));

        return $date;
    }

    return new \DateTime((string) $value, new \DateTimeZone('UTC'));
}

function embed_registry_is_same_context(Embed $embed, Course $course, ?Session $session): bool
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

function embed_registry_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function embed_registry_action_icon(string $url, string $icon, string $label, string $variant = 'primary', array $attributes = []): string
{
    $variantClasses = [
        'primary' => 'border-blue-100 text-blue-700 hover:bg-blue-50',
        'secondary' => 'border-orange-100 text-orange-700 hover:bg-orange-50',
        'danger' => 'border-red-100 text-red-700 hover:bg-red-50',
    ];

    $class = $variantClasses[$variant] ?? $variantClasses['primary'];
    $attributeHtml = '';

    foreach ($attributes as $name => $value) {
        $attributeHtml .= ' '.embed_registry_escape((string) $name).'="'.embed_registry_escape((string) $value).'"';
    }

    return sprintf(
        '<a href="%s" class="inline-flex h-9 w-9 items-center justify-center rounded-full border bg-white %s" title="%s" aria-label="%s"%s><span class="mdi %s ch-tool-icon" aria-hidden="true"></span><span class="sr-only">%s</span></a>',
        embed_registry_escape($url),
        $class,
        embed_registry_escape($label),
        embed_registry_escape($label),
        $attributeHtml,
        embed_registry_escape($icon),
        embed_registry_escape($label)
    );
}

function embed_registry_get_status(Embed $embed): array
{
    $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    $startDate = \DateTimeImmutable::createFromMutable($embed->getDisplayStartDate());
    $endDate = \DateTimeImmutable::createFromMutable($embed->getDisplayEndDate());

    if ($now < $startDate) {
        return [
            'label' => get_lang('NotYetAvailable'),
            'class' => 'bg-blue-100 text-blue-700',
        ];
    }

    if ($now > $endDate) {
        return [
            'label' => get_lang('Expired'),
            'class' => 'bg-gray-100 text-gray-700',
        ];
    }

    return [
        'label' => get_lang('Available'),
        'class' => 'bg-green-100 text-green-700',
    ];
}

function embed_registry_render_list(array $embeds, bool $isAllowedToEdit, EmbedRegistryPlugin $plugin, string $deleteToken): string
{
    if (empty($embeds)) {
        return '<div class="rounded-2xl border border-gray-20 bg-gray-10 p-6 text-center text-sm text-gray-50">'
            .embed_registry_escape(get_lang('No data available'))
            .'</div>';
    }

    $headers = [
        get_lang('Title'),
        get_lang('AvailableFrom'),
        get_lang('AvailableTill'),
        get_lang('Status'),
    ];

    if ($isAllowedToEdit) {
        $headers[] = get_lang('Members');
    }

    $headers[] = get_lang('Actions');

    $html = '<table class="w-full min-w-full border-collapse text-sm">';
    $html .= '<thead><tr class="border-b border-gray-20 bg-gray-10 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">';

    foreach ($headers as $header) {
        $alignClass = in_array($header, [get_lang('Members'), get_lang('Actions')], true) ? ' text-right' : '';
        $html .= '<th class="px-4 py-3'.$alignClass.'">'.embed_registry_escape((string) $header).'</th>';
    }

    $html .= '</tr></thead><tbody>';

    foreach ($embeds as $embed) {
        $status = embed_registry_get_status($embed);
        $actions = [];

        $actions[] = embed_registry_action_icon(
            $plugin->getViewUrl($embed),
            'mdi-eye',
            get_lang('View'),
            'primary'
        );

        if ($isAllowedToEdit) {
            $actions[] = embed_registry_action_icon(
                embed_registry_get_url(['action' => 'edit', 'id' => $embed->getId()]),
                'mdi-pencil',
                get_lang('Edit'),
                'secondary'
            );

            $actions[] = embed_registry_action_icon(
                embed_registry_get_url(
                    [
                        'action' => 'delete',
                        'id' => $embed->getId(),
                        'embed_registry_sec_token' => $deleteToken,
                    ]
                ),
                'mdi-delete',
                get_lang('Delete'),
                'danger',
                [
                    'onclick' => 'return confirm(\''.addslashes(get_lang('Please confirm your choice')).'\');',
                ]
            );
        }

        $membersCount = '-';

        if ($isAllowedToEdit) {
            try {
                $membersCount = (string) $plugin->getMembersCount($embed);
            } catch (\Throwable $exception) {
                error_log('[EmbedRegistry] Unable to count members: '.$exception->getMessage());
            }
        }

        $html .= '<tr class="border-b border-gray-20 hover:bg-gray-10">';
        $html .= '<td class="px-4 py-3 font-semibold text-gray-90">'.embed_registry_escape($embed->getTitle()).'</td>';
        $html .= '<td class="px-4 py-3 text-gray-70">'.embed_registry_escape(api_convert_and_format_date($embed->getDisplayStartDate())).'</td>';
        $html .= '<td class="px-4 py-3 text-gray-70">'.embed_registry_escape(api_convert_and_format_date($embed->getDisplayEndDate())).'</td>';
        $html .= '<td class="px-4 py-3"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold '.$status['class'].'">'.embed_registry_escape((string) $status['label']).'</span></td>';

        if ($isAllowedToEdit) {
            $html .= '<td class="px-4 py-3 text-right text-gray-70">'.embed_registry_escape($membersCount).'</td>';
        }

        $html .= '<td class="px-4 py-3 text-right"><div class="inline-flex items-center justify-end gap-2">'.implode('', $actions).'</div></td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    return $html;
}

$plugin = EmbedRegistryPlugin::create();

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$isAllowedToEdit = api_is_allowed_to_edit(true);
$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : null;

if (!in_array($action, ['add', 'edit', 'delete'], true)) {
    $action = null;
}

$em = Database::getManager();
$embedRepo = $em->getRepository(Embed::class);

$course = api_get_course_entity(api_get_course_int_id());
$session = api_get_session_entity(api_get_session_id());

$actions = [];
$deleteToken = Security::get_existing_token('embed_registry');

$view = new Template($plugin->getToolTitle());
$view->assign('is_allowed_to_edit', $isAllowedToEdit);

switch ($action) {
    case 'add':
        if (!$isAllowedToEdit) {
            api_not_allowed(true);
        }

        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            embed_registry_get_url()
        );

        $form = new FormValidator('frm_edit', 'post', embed_registry_get_url(['action' => 'add']));
        $form->addText(
            'title',
            [get_lang('Title'), $plugin->get_lang('EmbedTitleHelp')],
            true
        );
        $form->addDateRangePicker(
            'range',
            [get_lang('Date range'), $plugin->get_lang('EmbedDateRangeHelp')]
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

            $startDate = embed_registry_to_datetime(api_get_utc_datetime($values['range_start'], false, true));
            $endDate = embed_registry_to_datetime(api_get_utc_datetime($values['range_end'], false, true));

            if ($startDate > $endDate) {
                Display::addFlash(
                    Display::return_message($plugin->get_lang('InvalidDateRange'), 'warning')
                );
            } else {
                $embed = new Embed();
                $embed
                    ->setTitle((string) $values['title'])
                    ->setDisplayStartDate($startDate)
                    ->setDisplayEndDate($endDate)
                    ->setHtmlCode((string) $values['html_code'])
                    ->setCourse($course)
                    ->setSession($session);

                $em->persist($embed);
                $em->flush();

                Display::addFlash(
                    Display::return_message(get_lang('Added'), 'success')
                );

                header('Location: '.embed_registry_get_url());
                exit;
            }
        }

        $view->assign('header', $plugin->get_lang('CreateEmbeddable'));
        $view->assign('form', $form->returnForm());

        $externalUrl = $plugin->getExternalUrl();

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
            embed_registry_get_url()
        );

        $embedId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        if (!$embedId) {
            api_not_allowed(true);
        }

        /** @var Embed|null $embed */
        $embed = $embedRepo->find($embedId);

        if (!$embed || !embed_registry_is_same_context($embed, $course, $session)) {
            api_not_allowed(true);
        }

        $form = new FormValidator('frm_edit', 'post', embed_registry_get_url(['action' => 'edit', 'id' => $embedId]));
        $form->addText('title', get_lang('Title'), true);
        $form->addDateRangePicker('range', get_lang('Date range'));
        $form->addTextarea('html_code', $plugin->get_lang('HtmlCode'), ['rows' => 5], true);
        $form->addButtonUpdate(get_lang('Edit'));
        $form->addHidden('id', $embed->getId());
        $form->addHidden('action', 'edit');

        if ($form->validate()) {
            $values = $form->exportValues();

            $startDate = embed_registry_to_datetime(api_get_utc_datetime($values['range_start'], false, true));
            $endDate = embed_registry_to_datetime(api_get_utc_datetime($values['range_end'], false, true));

            if ($startDate > $endDate) {
                Display::addFlash(
                    Display::return_message($plugin->get_lang('InvalidDateRange'), 'warning')
                );
            } else {
                $embed
                    ->setTitle((string) $values['title'])
                    ->setDisplayStartDate($startDate)
                    ->setDisplayEndDate($endDate)
                    ->setHtmlCode((string) $values['html_code']);

                $em->persist($embed);
                $em->flush();

                Display::addFlash(
                    Display::return_message(get_lang('Updated'), 'success')
                );

                header('Location: '.embed_registry_get_url());
                exit;
            }
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
        if (!$isAllowedToEdit) {
            api_not_allowed(true);
        }

        if (!Security::check_token('get', null, 'embed_registry')) {
            Display::addFlash(
                Display::return_message($plugin->get_lang('InvalidSecurityToken'), 'warning')
            );

            header('Location: '.embed_registry_get_url());
            exit;
        }

        $embedId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        if (!$embedId) {
            api_not_allowed(true);
        }

        /** @var Embed|null $embed */
        $embed = $embedRepo->find($embedId);

        if (!$embed || !embed_registry_is_same_context($embed, $course, $session)) {
            api_not_allowed(true);
        }

        $em->remove($embed);
        $em->flush();
        Security::clear_token('embed_registry');

        Display::addFlash(
            Display::return_message(get_lang('Deleted'), 'success')
        );

        header('Location: '.embed_registry_get_url());
        exit;
    default:
        $currentEmbed = $plugin->getCurrentEmbed($course, $session);

        /** @var array|Embed[] $embeds */
        $embeds = $embedRepo->findBy(['course' => $course, 'session' => $session], ['displayStartDate' => 'DESC']);

        if ($isAllowedToEdit) {
            $btnAdd = Display::toolbarButton(
                $plugin->get_lang('CreateEmbeddable'),
                embed_registry_get_url(['action' => 'add']),
                'file-code-o',
                'primary'
            );

            $view->assign(
                'actions',
                Display::toolbarAction($plugin->get_name(), [$btnAdd])
            );
        }

        if ($currentEmbed) {
            $view->assign('current_embed', $currentEmbed);
            $view->assign(
                'current_link',
                Display::toolbarButton(
                    $plugin->get_lang('LaunchContent'),
                    $plugin->getViewUrl($currentEmbed),
                    'rocket',
                    'info'
                )
            );
        }

        $view->assign('embeds', $embeds);
        $view->assign('table', embed_registry_render_list($embeds, $isAllowedToEdit, $plugin, $deleteToken));
}

$content = $view->fetch('EmbedRegistry/view/start.tpl');

if ($actions) {
    $actions = implode(PHP_EOL, $actions);

    $view->assign(
        'actions',
        Display::toolbarAction($plugin->get_name(), [$actions])
    );
}

$view->assign('content', $content);
$view->display_one_col_template();
