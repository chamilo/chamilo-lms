<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;

/**
 * This script initiates a video conference session, calling the BigBlueButton API.
 */
$course_plugin = 'bbb'; // needed in order to load the plugin lang variables
$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

/**
 * Add classes to a DOM element without removing existing classes.
 */
function bbb_admin_add_classes_to_element(DOMElement $element, array $classes): void
{
    $existing = trim((string) $element->getAttribute('class'));
    $currentClasses = '' === $existing ? [] : preg_split('/\s+/', $existing);
    $currentClasses = is_array($currentClasses) ? $currentClasses : [];

    foreach ($classes as $class) {
        if (!in_array($class, $currentClasses, true)) {
            $currentClasses[] = $class;
        }
    }

    $element->setAttribute('class', trim(implode(' ', array_filter($currentClasses))));
}

/**
 * Return the inner HTML of a DOM element.
 */
function bbb_admin_get_element_inner_html(DOMElement $element): string
{
    $html = '';

    foreach ($element->childNodes as $childNode) {
        $html .= $element->ownerDocument->saveHTML($childNode);
    }

    return $html;
}

/**
 * Style legacy forms with Tailwind utility classes.
 */
function bbb_admin_style_form_html(string $html): string
{
    if (!class_exists(DOMDocument::class) || '' === trim($html)) {
        return $html;
    }

    $previousUseInternalErrors = libxml_use_internal_errors(true);

    $document = new DOMDocument('1.0', 'UTF-8');
    $wrappedHtml = '<?xml encoding="utf-8" ?><div id="bbb-admin-form-root">'.$html.'</div>';

    $loaded = $document->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    if (!$loaded) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $html;
    }

    $xpath = new DOMXPath($document);
    $root = $document->getElementById('bbb-admin-form-root');

    if (!$root) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $html;
    }

    $forms = $xpath->query('.//form', $root);
    if ($forms) {
        foreach ($forms as $form) {
            if (!$form instanceof DOMElement) {
                continue;
            }

            bbb_admin_add_classes_to_element($form, ['space-y-6']);
        }
    }

    $fieldsets = $xpath->query('.//fieldset', $root);
    if ($fieldsets) {
        foreach ($fieldsets as $fieldset) {
            if (!$fieldset instanceof DOMElement) {
                continue;
            }

            bbb_admin_add_classes_to_element($fieldset, [
                'rounded-2xl',
                'border',
                'border-gray-25',
                'bg-white',
                'p-5',
                'shadow-sm',
                'space-y-4',
            ]);
        }
    }

    $legends = $xpath->query('.//legend', $root);
    if ($legends) {
        foreach ($legends as $legend) {
            if (!$legend instanceof DOMElement) {
                continue;
            }

            bbb_admin_add_classes_to_element($legend, [
                'px-2',
                'text-body-2',
                'font-semibold',
                'text-gray-90',
            ]);
        }
    }

    $formGroups = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " form-group ")]', $root);
    if ($formGroups) {
        foreach ($formGroups as $group) {
            if (!$group instanceof DOMElement) {
                continue;
            }

            bbb_admin_add_classes_to_element($group, [
                'rounded-xl',
                'border',
                'border-gray-25',
                'bg-support-2',
                'p-4',
                'space-y-3',
            ]);
        }
    }

    $labels = $xpath->query('.//label', $root);
    if ($labels) {
        foreach ($labels as $label) {
            if (!$label instanceof DOMElement) {
                continue;
            }

            bbb_admin_add_classes_to_element($label, [
                'block',
                'text-body-2',
                'font-semibold',
                'text-gray-90',
            ]);
        }
    }

    $inputs = $xpath->query('.//input[not(@type="hidden") and not(@type="submit") and not(@type="button") and not(@type="checkbox") and not(@type="radio")] | .//select | .//textarea', $root);
    if ($inputs) {
        foreach ($inputs as $input) {
            if (!$input instanceof DOMElement) {
                continue;
            }

            bbb_admin_add_classes_to_element($input, [
                'mt-2',
                'block',
                'w-full',
                'rounded-lg',
                'border',
                'border-gray-25',
                'bg-white',
                'px-3',
                'py-2',
                'text-body-2',
                'text-gray-90',
                'shadow-sm',
                'focus:border-primary',
                'focus:ring-primary',
            ]);
        }
    }

    $checkboxContainers = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " checkbox ")
            or contains(concat(" ", normalize-space(@class), " "), " radio ")]',
        $root
    );
    if ($checkboxContainers) {
        foreach ($checkboxContainers as $container) {
            if (!$container instanceof DOMElement) {
                continue;
            }

            bbb_admin_add_classes_to_element($container, ['flex', 'items-center', 'gap-3']);
        }
    }

    $helpBlocks = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " help-block ")
            or contains(concat(" ", normalize-space(@class), " "), " form-control-feedback ")]',
        $root
    );
    if ($helpBlocks) {
        foreach ($helpBlocks as $helpBlock) {
            if (!$helpBlock instanceof DOMElement) {
                continue;
            }

            bbb_admin_add_classes_to_element($helpBlock, [
                'mt-2',
                'block',
                'text-caption',
                'text-gray-50',
            ]);
        }
    }

    $buttons = $xpath->query('.//button | .//input[@type="submit"] | .//input[@type="button"]', $root);
    if ($buttons) {
        foreach ($buttons as $button) {
            if (!$button instanceof DOMElement) {
                continue;
            }

            bbb_admin_add_classes_to_element($button, [
                'inline-flex',
                'items-center',
                'justify-center',
                'rounded-lg',
                'border',
                'border-primary',
                'bg-primary',
                'px-4',
                'py-2',
                'text-body-2',
                'font-semibold',
                'text-white',
                'shadow-sm',
                'transition',
                'hover:opacity-90',
            ]);
        }
    }

    $result = bbb_admin_get_element_inner_html($root);

    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    return $result;
}

/**
 * Style action links generated by legacy helpers.
 */
function bbb_admin_style_action_links_html(string $html): string
{
    if (!class_exists(DOMDocument::class) || '' === trim($html)) {
        return $html;
    }

    $previousUseInternalErrors = libxml_use_internal_errors(true);

    $document = new DOMDocument('1.0', 'UTF-8');
    $wrappedHtml = '<?xml encoding="utf-8" ?><div id="bbb-admin-links-root">'.$html.'</div>';

    $loaded = $document->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    if (!$loaded) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $html;
    }

    $xpath = new DOMXPath($document);
    $root = $document->getElementById('bbb-admin-links-root');

    if (!$root) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $html;
    }

    $anchors = $xpath->query('.//a', $root);
    if ($anchors) {
        foreach ($anchors as $anchor) {
            if (!$anchor instanceof DOMElement) {
                continue;
            }

            $href = strtolower((string) $anchor->getAttribute('href'));
            $classes = [
                'mb-1',
                'mr-1',
                'inline-flex',
                'items-center',
                'gap-1.5',
                'rounded-md',
                'border',
                'border-gray-25',
                'bg-white',
                'px-3',
                'py-2',
                'text-caption',
                'font-semibold',
                'text-gray-90',
                'no-underline',
                'shadow-sm',
                'transition',
                'hover:bg-gray-15',
                'hover:text-white',
            ];

            if (str_contains($href, 'delete')) {
                $classes = [
                    'mb-1',
                    'mr-1',
                    'inline-flex',
                    'items-center',
                    'gap-1.5',
                    'rounded-md',
                    'border',
                    'border-danger/20',
                    'bg-danger/10',
                    'px-3',
                    'py-2',
                    'text-caption',
                    'font-semibold',
                    'text-danger',
                    'no-underline',
                    'transition',
                    'hover:opacity-90',
                ];
            } elseif (str_contains($href, 'publish')) {
                $classes = [
                    'mb-1',
                    'mr-1',
                    'inline-flex',
                    'items-center',
                    'gap-1.5',
                    'rounded-md',
                    'border',
                    'border-success/20',
                    'bg-success/10',
                    'px-3',
                    'py-2',
                    'text-caption',
                    'font-semibold',
                    'text-success',
                    'no-underline',
                    'transition',
                    'hover:opacity-90',
                ];
            }

            bbb_admin_add_classes_to_element($anchor, $classes);
        }
    }

    $images = $xpath->query('.//img', $root);
    if ($images) {
        foreach ($images as $image) {
            if (!$image instanceof DOMElement) {
                continue;
            }

            bbb_admin_add_classes_to_element($image, ['h-4', 'w-4']);
        }
    }

    $spans = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " label ")]', $root);
    if ($spans) {
        foreach ($spans as $span) {
            if (!$span instanceof DOMElement) {
                continue;
            }

            $class = strtolower((string) $span->getAttribute('class'));
            $classes = [
                'inline-flex',
                'items-center',
                'rounded-full',
                'px-2.5',
                'py-1',
                'text-caption',
                'font-semibold',
            ];

            if (str_contains($class, 'success')) {
                $classes = array_merge($classes, ['bg-success/10', 'text-success']);
            } else {
                $classes = array_merge($classes, ['bg-info/10', 'text-info']);
            }

            $span->setAttribute('class', '');
            bbb_admin_add_classes_to_element($span, $classes);
        }
    }

    $result = bbb_admin_get_element_inner_html($root);

    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    return $result;
}

/**
 * Escape text for HTML output.
 */
function bbb_admin_escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Resolve a readable title from an entity or scalar.
 */
function bbb_admin_get_related_title(mixed $value): string
{
    if (empty($value)) {
        return '-';
    }

    if (is_object($value) && method_exists($value, 'getTitle')) {
        return (string) $value->getTitle();
    }

    return (string) $value;
}

/**
 * Render a status badge.
 */
function bbb_admin_render_status_badge(array $meeting, BbbPlugin $plugin): string
{
    $isOpen = (int) ($meeting['status'] ?? 0) === 1;
    $label = $isOpen
        ? $plugin->get_lang('MeetingOpened')
        : $plugin->get_lang('MeetingClosed');

    $classes = $isOpen
        ? 'bg-success/10 text-success'
        : 'bg-info/10 text-info';

    return '<span class="inline-flex items-center rounded-full px-2.5 py-1 text-caption font-semibold '.$classes.'">'.
        bbb_admin_escape($label).
        '</span>';
}

/**
 * Render record links safely inside a styled container.
 */
function bbb_admin_render_records(array $meeting, BbbPlugin $plugin): string
{
    if ((int) ($meeting['record'] ?? 0) !== 1) {
        return '<span class="text-gray-50">'.bbb_admin_escape($plugin->get_lang('NoRecording')).'</span>';
    }

    $links = (string) ($meeting['show_links'] ?? '');

    if ('' === trim($links)) {
        return '<span class="text-gray-50">-</span>';
    }

    return '<div class="flex flex-wrap">'.bbb_admin_style_action_links_html($links).'</div>';
}

/**
 * Render participant names and emails.
 */
function bbb_admin_render_participants(array $meeting): string
{
    $participants = $meeting['participants'] ?? [];

    if (empty($participants) || !is_array($participants)) {
        return '<span class="text-gray-50">-</span>';
    }

    $items = [];
    foreach ($participants as $participant) {
        $items[] = '<div class="rounded-md bg-support-2 px-3 py-2">'.bbb_admin_escape($participant).'</div>';
    }

    return '<div class="space-y-2">'.implode('', $items).'</div>';
}

/**
 * Render actions safely inside a styled container.
 */
function bbb_admin_render_actions(array $meeting): string
{
    $links = (string) ($meeting['action_links'] ?? '');

    if ('' === trim($links)) {
        return '<span class="text-gray-50">-</span>';
    }

    return '<div class="flex flex-wrap">'.bbb_admin_style_action_links_html($links).'</div>';
}

/**
 * Render the meetings table.
 */
function bbb_admin_render_meetings_table(array $meetings, BbbPlugin $plugin): string
{
    if (empty($meetings)) {
        return '
            <div class="rounded-2xl border border-dashed border-gray-25 bg-white px-6 py-10 text-center shadow-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-support-2 text-white">
                    <em class="mdi mdi-video-off-outline text-3xl"></em>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-90">'.bbb_admin_escape($plugin->get_lang('RecordList')).'</h3>
                <p class="mt-2 text-body-2 text-gray-50">'.bbb_admin_escape(get_lang('No results')).'</p>
            </div>
        ';
    }

    $rows = '';

    foreach ($meetings as $meeting) {
        $createdAt = bbb_admin_escape($meeting['created_at'] ?? '-');
        $closedAt = bbb_admin_escape($meeting['closed_at'] ?? '-');
        $courseTitle = bbb_admin_escape(bbb_admin_get_related_title($meeting['course'] ?? null));
        $sessionTitle = bbb_admin_escape(bbb_admin_get_related_title($meeting['session'] ?? null));
        $participantCount = is_array($meeting['participants'] ?? null) ? count($meeting['participants']) : 0;
        $rowClasses = ((int) ($meeting['visibility'] ?? 1) === 0)
            ? 'bg-gray-5'
            : 'bg-white';

        $rows .= '
            <tr id="meeting-'.bbb_admin_escape($meeting['id'] ?? '').'" class="border-t border-gray-20 '.$rowClasses.' align-top transition hover:bg-gray-15">
                <td class="px-4 py-4 text-body-2 text-gray-90">'.$createdAt.'</td>
                <td class="px-4 py-4 text-body-2 text-gray-90">'.$closedAt.'</td>
                <td class="px-4 py-4">'.bbb_admin_render_status_badge($meeting, $plugin).'</td>
                <td class="px-4 py-4">'.bbb_admin_render_records($meeting, $plugin).'</td>
                <td class="px-4 py-4 text-body-2 text-gray-90">'.$courseTitle.'</td>
                <td class="px-4 py-4 text-body-2 text-gray-90">'.$sessionTitle.'</td>
                <td class="px-4 py-4 text-body-2 text-gray-90">'.bbb_admin_render_participants($meeting).'</td>
                <td class="px-4 py-4 text-body-2 font-semibold text-gray-90">'.bbb_admin_escape($participantCount).'</td>
                <td class="px-4 py-4">'.bbb_admin_render_actions($meeting).'</td>
            </tr>
        ';
    }

    return '
        <div class="overflow-x-auto rounded-2xl border border-gray-25 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-25">
                <thead class="bg-gray-15">
                    <tr>
                        <th class="px-4 py-3 text-left text-caption font-semibold uppercase tracking-wide text-gray-50">'.bbb_admin_escape(get_lang('Start date')).'</th>
                        <th class="px-4 py-3 text-left text-caption font-semibold uppercase tracking-wide text-gray-50">'.bbb_admin_escape(get_lang('End date')).'</th>
                        <th class="px-4 py-3 text-left text-caption font-semibold uppercase tracking-wide text-gray-50">'.bbb_admin_escape(get_lang('Status')).'</th>
                        <th class="px-4 py-3 text-left text-caption font-semibold uppercase tracking-wide text-gray-50">'.bbb_admin_escape($plugin->get_lang('Records')).'</th>
                        <th class="px-4 py-3 text-left text-caption font-semibold uppercase tracking-wide text-gray-50">'.bbb_admin_escape(get_lang('Course')).'</th>
                        <th class="px-4 py-3 text-left text-caption font-semibold uppercase tracking-wide text-gray-50">'.bbb_admin_escape(get_lang('Session')).'</th>
                        <th class="px-4 py-3 text-left text-caption font-semibold uppercase tracking-wide text-gray-50">'.bbb_admin_escape(get_lang('Participants')).'</th>
                        <th class="px-4 py-3 text-left text-caption font-semibold uppercase tracking-wide text-gray-50">'.bbb_admin_escape(get_lang('CountUsers')).'</th>
                        <th class="px-4 py-3 text-left text-caption font-semibold uppercase tracking-wide text-gray-50">'.bbb_admin_escape(get_lang('Actions')).'</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-20">
                    '.$rows.'
                </tbody>
            </table>
        </div>
    ';
}

$plugin = BbbPlugin::create();
$tool_name = $plugin->get_lang('Videoconference');
$isGlobal = isset($_GET['global']);

$bbb = new Bbb('', '', $isGlobal);
$action = $_GET['action'] ?? null;

$currentMonth = date('n');
$dateStart = $_REQUEST['search_meeting_start'] ?? date('Y-m-d', mktime(1, 1, 1, $currentMonth, 1, date('Y')));
$dateEnd = $_REQUEST['search_meeting_end'] ?? date('Y-m-d', mktime(1, 1, 1, ++$currentMonth, 0, date('Y')));

$dateRange = [
    'search_meeting_start' => $dateStart,
    'search_meeting_end' => $dateEnd,
];

$form = new FormValidator(get_lang('Search'));
$form->addDatePicker('search_meeting_start', get_lang('Start date'));
$form->addDatePicker('search_meeting_end', get_lang('End date'));
$form->addButtonSearch(get_lang('Search'));
$form->setDefaults($dateRange);

if ($form->validate()) {
    $dateRange = $form->getSubmitValues();
}

$meetings = $bbb->getMeetings(0, 0, 0, true, $dateRange);

foreach ($meetings as &$meeting) {
    $participants = $bbb->findConnectedMeetingParticipants($meeting['id']);
    foreach ($participants as $meetingParticipant) {
        /** @var User $participant */
        $participant = $meetingParticipant['participant'];
        if ($participant) {
            $meeting['participants'][] = UserManager::formatUserFullName($participant).' ('.$participant->getEmail().')';
        }
    }
}
unset($meeting);

if ($action) {
    switch ($action) {
        case 'export':
            $dataToExport = [
                [$tool_name, $plugin->get_lang('RecordList')],
                [],
                [
                    get_lang('Created at'),
                    get_lang('Status'),
                    $plugin->get_lang('Records'),
                    get_lang('Course'),
                    get_lang('Session'),
                    get_lang('Participants'),
                ],
            ];

            foreach ($meetings as $meeting) {
                $dataToExport[] = [
                    $meeting['created_at'],
                    1 == $meeting['status'] ? $plugin->get_lang('MeetingOpened') : $plugin->get_lang('MeetingClosed'),
                    1 == $meeting['record'] ? get_lang('Yes') : get_lang('No'),
                    $meeting['course'] ? $meeting['course']->getTitle() : '-',
                    $meeting['session'] ? $meeting['session']->getTitle() : '-',
                    isset($meeting['participants']) ? implode(PHP_EOL, $meeting['participants']) : null,
                ];
            }

            Export::arrayToXls($dataToExport);
            break;
    }
}

if (!empty($meetings)) {
    $meetings = array_reverse($meetings);
}

$serverRunning = $bbb->isServerRunning();
if (!$serverRunning) {
    Display::addFlash(
        Display::return_message(get_lang('ServerIsNotRunning'), 'error')
    );
}

$htmlHeadXtra[] = api_get_js_simple(
    api_get_path(WEB_PLUGIN_PATH).'Bbb/resources/utils.js'
);
$htmlHeadXtra[] = "<script>var _p = {web_plugin: '".api_get_path(WEB_PLUGIN_PATH)."'};</script>";

$settingsForm = new FormValidator('settings', api_get_self());
$settingsForm->addHeader($plugin->get_lang('UpdateAllCourseSettings'));
$settingsForm->addHtml(
    '<div class="rounded-xl border border-warning/20 bg-warning/10 px-4 py-3 text-body-2 text-gray-90">'.
    bbb_admin_escape($plugin->get_lang('ThisWillUpdateAllSettingsInAllCourses')).
    '</div>'
);
$settings = $plugin->course_settings;
$defaults = [];
foreach ($settings as $setting) {
    $setting = $setting['name'];
    $text = $settingsForm->addText($setting, $plugin->get_lang($setting), false);
    $text->freeze();
    $defaults[$setting] = 'true' === api_get_plugin_setting('bbb', $setting) ? get_lang('Yes') : get_lang('No');
}

$settingsForm->addButtonSave($plugin->get_lang('UpdateAllCourses'));

if ($settingsForm->validate()) {
    $table = Database::get_course_table(TABLE_COURSE_SETTING);
    foreach ($settings as $setting) {
        $setting = $setting['name'];
        $setting = Database::escape_string($setting);

        if (empty($setting)) {
            continue;
        }

        $value = api_get_plugin_setting('bbb', $setting);
        if ('true' === $value) {
            $value = 1;
        } else {
            $value = '';
        }

        $sql = "UPDATE $table SET value = '$value' WHERE variable = '$setting'";
        Database::query($sql);
    }

    Display::addFlash(Display::return_message(get_lang('Updated')));
    header('Location: '.api_get_self());
    exit;
}

$settingsForm->setDefaults($defaults);

$totalMeetings = count($meetings);
$openMeetings = 0;
$recordedMeetings = 0;
$totalParticipants = 0;

foreach ($meetings as $meeting) {
    if ((int) ($meeting['status'] ?? 0) === 1) {
        ++$openMeetings;
    }

    if ((int) ($meeting['record'] ?? 0) === 1) {
        ++$recordedMeetings;
    }

    $totalParticipants += is_array($meeting['participants'] ?? null) ? count($meeting['participants']) : 0;
}

$closedMeetings = max(0, $totalMeetings - $openMeetings);
$searchFormHtml = bbb_admin_style_form_html($form->returnForm());
$settingsFormHtml = bbb_admin_style_form_html($settingsForm->returnForm());
$meetingsTableHtml = bbb_admin_render_meetings_table($meetings, $plugin);

$exportUrl = api_get_self().'?'.http_build_query([
        'action' => 'export',
        'search_meeting_start' => $dateStart,
        'search_meeting_end' => $dateEnd,
    ]);

$content = '
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-support-2 px-3 py-1 text-caption font-semibold text-white">
                    <em class="mdi mdi-video-outline text-base"></em>
                    '.bbb_admin_escape($plugin->get_lang('RecordList')).'
                </div>
                <h2 class="mt-3 text-2xl font-semibold text-gray-90">'.bbb_admin_escape($tool_name).'</h2>
                <p class="mt-2 max-w-3xl text-body-2 text-gray-50">'.
    bbb_admin_escape(get_lang('Manage meetings, review recordings and update course-wide BBB settings from one place.')).
    '</p>
            </div>'.
    (!empty($meetings) ? '
            <div class="flex flex-wrap gap-3">
                <a href="'.bbb_admin_escape($exportUrl).'" class="inline-flex items-center gap-2 rounded-lg border border-success bg-success px-4 py-2 text-body-2 font-semibold text-success-button-text shadow-sm transition hover:opacity-90">
                    <em class="mdi mdi-file-excel-outline text-base"></em>
                    '.bbb_admin_escape(get_lang('Export in Excel format')).'
                </a>
            </div>' : '').'
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-25 bg-support-2 p-4">
                <div class="text-caption font-semibold uppercase tracking-wide text-gray-50">'.bbb_admin_escape(get_lang('Total')).'</div>
                <div class="mt-2 text-2xl font-semibold text-gray-90">'.bbb_admin_escape($totalMeetings).'</div>
            </div>
            <div class="rounded-xl border border-success/20 bg-success/10 p-4">
                <div class="text-caption font-semibold uppercase tracking-wide text-gray-50">'.bbb_admin_escape($plugin->get_lang('MeetingOpened')).'</div>
                <div class="mt-2 text-2xl font-semibold text-success">'.bbb_admin_escape($openMeetings).'</div>
            </div>
            <div class="rounded-xl border border-info/20 bg-info/10 p-4">
                <div class="text-caption font-semibold uppercase tracking-wide text-gray-50">'.bbb_admin_escape($plugin->get_lang('MeetingClosed')).'</div>
                <div class="mt-2 text-2xl font-semibold text-info">'.bbb_admin_escape($closedMeetings).'</div>
            </div>
            <div class="rounded-xl border border-primary/20 bg-primary/10 p-4">
                <div class="text-caption font-semibold uppercase tracking-wide text-gray-50">'.bbb_admin_escape(get_lang('Participants')).'</div>
                <div class="mt-2 text-2xl font-semibold text-white">'.bbb_admin_escape($totalParticipants).'</div>
                <div class="mt-1 text-caption text-gray-50">'.bbb_admin_escape($recordedMeetings).' '.bbb_admin_escape($plugin->get_lang('Records')).'</div>
            </div>
        </div>
    </section>'.
    (!$serverRunning ? '
    <section class="rounded-2xl border border-danger/20 bg-danger/10 p-5 shadow-sm">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 text-danger">
                <em class="mdi mdi-alert-circle-outline text-2xl"></em>
            </div>
            <div>
                <h3 class="text-base font-semibold text-danger">'.bbb_admin_escape(get_lang('Server status')).'</h3>
                <p class="mt-1 text-body-2 text-gray-90">'.bbb_admin_escape(get_lang('ServerIsNotRunning')).'</p>
            </div>
        </div>
    </section>' : '').'
    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-90">'.bbb_admin_escape(get_lang('Search')).'</h3>
                <p class="mt-1 text-body-2 text-gray-50">'.bbb_admin_escape(get_lang('Filter the list by meeting date range.')).'</p>
            </div>
            '.$searchFormHtml.'
        </div>

        <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-90">'.bbb_admin_escape($plugin->get_lang('UpdateAllCourseSettings')).'</h3>
                <p class="mt-1 text-body-2 text-gray-50">'.bbb_admin_escape(get_lang('Review the current defaults and propagate them to every course when needed.')).'</p>
            </div>
            '.$settingsFormHtml.'
        </div>
    </section>

    <section class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-90">'.bbb_admin_escape($plugin->get_lang('RecordList')).'</h3>
                <p class="text-body-2 text-gray-50">'.bbb_admin_escape(get_lang('Review meeting history, participants and recording actions.')).'</p>
            </div>
        </div>
        '.$meetingsTableHtml.'
    </section>
</div>';

$tpl = new Template($tool_name);
$tpl->assign('header', $plugin->get_lang('RecordList'));
$tpl->assign('content', $content);
$tpl->display_one_col_template();
