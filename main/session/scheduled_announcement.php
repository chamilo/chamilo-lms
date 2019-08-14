<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script(true);

$sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

$sessionInfo = api_get_session_info($sessionId);
if (!$sessionInfo) {
    api_not_allowed(true);
}

$object = new ScheduledAnnouncement();

if (!$object->allowed()) {
    api_not_allowed(true);
}

$sessionUrl = api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$sessionId;

$htmlHeadXtra[] = api_get_jqgrid_js();
$interbreadcrumb[] = [
    'url' => 'session_list.php',
    'name' => get_lang('SessionList'),
];
$interbreadcrumb[] = [
    'url' => $sessionUrl,
    'name' => get_lang('SessionOverview'),
];

$tool_name = get_lang('ScheduledAnnouncements');

switch ($action) {
    case 'run':
        $messagesSent = $object->sendPendingMessages();
        Display::addFlash(
            Display::return_message(
                get_lang('MessageSent').': '.$messagesSent,
                'confirmation'
            )
        );
        $content = $object->getGrid($sessionId);
        break;
    case 'add':
        $interbreadcrumb[] = [
            'url' => api_get_self().'?session_id='.$sessionId,
            'name' => get_lang('ScheduledAnnouncements'),
        ];
        $tool_name = get_lang('Add');

        $url = api_get_self().'?action=add&session_id='.$sessionId;
        $form = $object->returnForm($url, 'add', $sessionInfo);

        // The validation or display
        if ($form->validate()) {
            $values = $form->getSubmitValues();
            switch ($values['type']) {
                case 'base_date':
                    $numberDays = (int) $values['days'];
                    switch ($values['base_date']) {
                        case 'start_date':
                            $baseDate = new DateTime($sessionInfo['access_start_date']);
                            break;
                        case 'end_date':
                            $baseDate = new DateTime($sessionInfo['access_end_date']);
                            break;
                    }
                    $interval = new DateInterval('P'.$numberDays.'D');
                    switch ($values['moment_type']) {
                        case 'after':
                            $newDate = $baseDate->add($interval);
                            break;
                        case 'before':
                            $newDate = $baseDate->sub($interval);
                            break;
                    }
                    $values['date'] = $newDate->format('Y-m-d h:i:s');
                    break;
                case 'specific_date':
                    $values['date'] = api_get_utc_datetime($values['date']);
                    break;
            }

            $res = $object->save($values);

            if ($res) {
                $extraFieldValue = new ExtraFieldValue('scheduled_announcement');
                $values['item_id'] = $res;
                $extraFieldValue->saveFieldValues($values);

                Display::addFlash(
                    Display::return_message(
                        get_lang('ItemAdded'),
                        'confirmation'
                    )
                );
            }
            $content = $object->getGrid($sessionId);
        } else {
            $content = '<div class="actions">';
            $content .= Display::url(
                Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM),
                api_get_self().'?session_id='.$sessionId
            );
            $content .= '</div>';
            $form->addElement('hidden', 'sec_token');

            $content .= $form->returnForm();
        }
        break;
    case 'edit':
        $tool_name = get_lang('Edit');
        $interbreadcrumb[] = [
            'url' => api_get_self().'?session_id='.$sessionId,
            'name' => get_lang('ScheduledAnnouncements'),
        ];

        // Action handling: Editing
        $url = api_get_self().'?action=edit&id='.$id.'&session_id='.$sessionId;
        $form = $object->returnSimpleForm($id, $url, 'edit', $sessionInfo);
        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $values['id'] = $id;
            $values['sent'] = isset($values['sent']) ? 1 : '';
            $values['date'] = api_get_utc_datetime($values['date']);
            $res = $object->update($values);

            $extraFieldValue = new ExtraFieldValue('scheduled_announcement');
            $values['item_id'] = $id;
            $extraFieldValue->saveFieldValues($values);

            Display::addFlash(Display::return_message(
                get_lang('Updated'),
                'confirmation'
            ));
            header("Location: $url");
            exit;
        }
        $item = $object->get($id);
        $item['date'] = api_get_local_time($item['date']);
        $form->setDefaults($item);
        $content = $form->returnForm();
        break;
    case 'delete':
        $object->delete($id);
        $content = $object->getGrid($sessionId);
        break;
    default:
        $content = $object->getGrid($sessionId);
        break;
}

$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_programmed_announcements&session_id='.$sessionId;

$columns = [
    get_lang('Subject'),
    get_lang('Date'),
    get_lang('Sent'),
    get_lang('Actions'),
];

$columnModel = [
    [
        'name' => 'subject',
        'index' => 'subject',
        'width' => '250',
        'align' => 'left',
    ],
    [
        'name' => 'date',
        'index' => 'date',
        //'width' => '90',
        //'align' => 'left',
        'sortable' => 'true',
    ],
    [
        'name' => 'sent',
        'index' => 'sent',
        //'width' => '90',
        //'align' => 'left',
        'sortable' => 'true',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '100',
        'align' => 'left',
        'formatter' => 'action_formatter',
        'sortable' => 'false',
    ],
];

$actionLinks = 'function action_formatter(cellvalue, options, rowObject) {
    return \'<a href="?action=edit&session_id='.$sessionId.'&id=\'+options.rowId+\'">'.Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>'.
    '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;"  href="?action=delete&session_id='.$sessionId.'&id=\'+options.rowId+\'">'.Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>'.
    '\';
}';

$extraParams = [];
$extraParams['autowidth'] = 'true';

$htmlHeadXtra[] = '<script>
$(function() {
    // grid definition see the $obj->display() function
    '.Display::grid_js(
        'programmed',
        $url,
        $columns,
        $columnModel,
        $extraParams,
        [],
        $actionLinks,
        true
    ).'
});
</script>';

$tpl = new Template($tool_name);
$sessionTitle = Display::page_header($sessionInfo['name']);
$tpl->assign('content', $sessionTitle.$content);
$tpl->display_one_col_template();
