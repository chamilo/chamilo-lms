<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldSavedSearch;

$cidReset = true;

require_once 'main/inc/global.inc.php';

api_block_anonymous_users();

//if (!api_is_platform_admin()) {
    if (!api_is_drh()) {
        api_not_allowed(true);
    }
//}
$userId = api_get_user_id();
$userInfo = api_get_user_info();

$userToLoad = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'subscribe_user':
        $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : '';
        SessionManager::suscribe_users_to_session($sessionId, [$userToLoad], SESSION_VISIBLE_READ_ONLY, false);
        Display::addFlash(Display::return_message(get_lang('UserAdded')));
        header("Location: ".api_get_self().'?user_id='.$userToLoad);
        exit;
        break;
    case 'unsubscribe_user':
        $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : '';
        SessionManager::unsubscribe_user_from_session($sessionId, $userToLoad);
        Display::addFlash(Display::return_message(get_lang('Unsubscribed')));
        header("Location: ".api_get_self().'?user_id='.$userToLoad);
        break;
}

$em = Database::getManager();

$formSearch = new FormValidator('load', 'get', api_get_self());
$formSearch->addHeader(get_lang('LoadDiagnosis'));
if (!empty($userInfo)) {
    if ($userInfo['status'] == DRH) {
        $users = UserManager::get_users_followed_by_drh($userId);
        if (!empty($users)) {
            $userList = [];
            foreach ($users as $user) {
                $userList[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']);
            }
            $formSearch->addSelect('user_id', get_lang('User'), $userList);
        }
    }
}
if ($userToLoad) {
    $formSearch->setDefaults(['user_id' => $userToLoad]);
}

$formSearch->addButtonSearch(get_lang('Search'), 'save');

$form = new FormValidator('search', 'post', api_get_self().'?user_id='.$userToLoad);
$form->addHeader(get_lang('Diagnosis'));
$form->addHidden('user_id', $userToLoad);

/** @var ExtraFieldSavedSearch  $saved */
$search = [
    'user' => $userToLoad
];

$items = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findBy($search);
if (empty($items)) {
    Display::addFlash(Display::return_message('NoData'));
}

$extraField = new ExtraField('session');
$extraFieldValue = new ExtraFieldValue('session');
$extra = $extraField->addElements($form, '', [], true);

$form->addButtonSearch(get_lang('Search'), 'save');

$extraFieldsToFilter = $extraField->get_all(array('variable = ?' => 'temps-de-travail'));
$extraFieldToSearch = array();
if (!empty($extraFieldsToFilter)) {
    foreach ($extraFieldsToFilter as $filter) {
        $extraFieldToSearch[] = $filter['id'];
    }
}
$extraFieldListToString = implode(',', $extraFieldToSearch);

$result = SessionManager::getGridColumns('simple', $extraFieldsToFilter);
$columns = $result['columns'];
$column_model = $result['column_model'];
$defaults = [];
$tagsData = [];
if (!empty($items)) {
    /** @var ExtraFieldSavedSearch $item */
    foreach ($items as $item) {
        $variable = 'extra_'.$item->getField()->getVariable();
        if ($item->getField()->getFieldType() == Extrafield::FIELD_TYPE_TAG) {
            $tagsData[$variable] = $item->getValue();
        }
        $defaults[$variable] = $item->getValue();
    }
}

$form->setDefaults($defaults);

$filterToSend = '';

if ($formSearch->validate()) {
    $formSearchParams = $formSearch->getSubmitValues();
    $filters = [];
    foreach ($defaults as $key => $value) {
        if (substr($key, 0, 6) != 'extra_' && substr($key, 0, 7) != '_extra_') {
            continue;
        }
        if (!empty($value)) {
            $filters[$key] = $value;
        }
    }

    //$defaults
    $filterToSend = [];
    if (!empty($filters)) {
        $filterToSend = ['groupOp' => 'AND'];
        if ($filters) {
            $count = 1;
            $countExtraField = 1;
            foreach ($result['column_model'] as $column) {
                if ($count > 5) {
                    if (isset($filters[$column['name']])) {
                        $defaultValues['jqg'.$countExtraField] = $filters[$column['name']];
                        $filterToSend['rules'][] = ['field' => $column['name'], 'op' => 'cn', 'data' => $filters[$column['name']]];
                    }
                    $countExtraField++;
                }
                $count++;
            }
        }
    }
}

if ($form->validate()) {
    $params = $form->getSubmitValues();
    if (isset($params['save'])) {
        unset($params['save']);
    }
    $form->setDefaults($params);

    // Search
    $filters = [];
    // Parse params.
    foreach ($params as $key => $value) {
        if (substr($key, 0, 6) != 'extra_' && substr($key, 0, 7) != '_extra_') {
            continue;
        }
        if (!empty($value)) {
            $filters[$key] = $value;
        }
    }
    $filterToSend = [];
    if (!empty($filters)) {
        $filterToSend = ['groupOp' => 'AND'];
        if ($filters) {
            $count = 1;
            $countExtraField = 1;
            foreach ($result['column_model'] as $column) {
                if ($count > 5) {
                    if (isset($filters[$column['name']])) {
                        $defaultValues['jqg'.$countExtraField] = $filters[$column['name']];
                        $filterToSend['rules'][] = ['field' => $column['name'], 'op' => 'cn', 'data' => $filters[$column['name']]];
                    }
                    $countExtraField++;
                }
                $count++;
            }
        }
    }
}

$view = $form->returnForm();

$jsTag = '';
if (!empty($tagsData)) {
    foreach ($tagsData as $extraField => $tags) {
        foreach ($tags as $tag) {
            $jsTag .= "$('#$extraField')[0].addItem('$tag', '$tag');";
        }
    }
}

$htmlHeadXtra[] ='<script>
$(function() {
    '.$extra['jquery_ready_content'].'
    '.$jsTag.'
});
</script>';

if (!empty($filterToSend)) {
    $filterToSend = json_encode($filterToSend);
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&_search=true&load_extra_field='.$extraFieldListToString.'&_force_search=true&rows=20&page=1&sidx=&sord=asc&filters2='.$filterToSend;
} else {
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&_search=true&load_extra_field='.$extraFieldListToString.'&_force_search=true&rows=20&page=1&sidx=&sord=asc';
}

// Autowidth
$extra_params['autowidth'] = 'true';

// height auto
$extra_params['height'] = 'auto';
$extra_params['postData'] = array(
    'filters' => array(
        "groupOp" => "AND",
        "rules" => $result['rules']
    )
);

$sessionByUserList = SessionManager::get_sessions_by_user($userToLoad, true, true);

$sessionUserList = array();
if (!empty($sessionByUserList)) {
    foreach ($sessionByUserList as $sessionByUser) {
        $sessionUserList[] = $sessionByUser['session_id'];
    }
}
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
    var sessionList = '.json_encode($sessionUserList).';
    if ($.inArray(options.rowId, sessionList) == -1) {
        return \'<a href="'.api_get_self().'?action=subscribe_user&user_id='.$userToLoad.'&session_id=\'+options.rowId+\'">'.Display::return_icon('add.png', addslashes(get_lang('Subscribe')),'',ICON_SIZE_SMALL).'</a>'.'\';
    } else {
        return \'<a href="'.api_get_self().'?action=unsubscribe_user&user_id='.$userToLoad.'&session_id=\'+options.rowId+\'">'.Display::return_icon('delete.png', addslashes(get_lang('Delete')),'',ICON_SIZE_SMALL).'</a>'.'\';
    }
}';

$htmlHeadXtra[] = api_get_jqgrid_js();

$griJs = Display::grid_js('sessions', $url, $columns, $column_model, $extra_params, array(), $action_links, true);
$grid = '<div id="session-table" class="table-responsive">';
$grid .= Display::grid_html('sessions');
$grid .= '</div>';

$tpl = new Template(get_lang('Diagnosis'));

if (empty($items)) {
    $view = '';
    $grid = '';
    $griJs = '';
}
$tpl->assign('form', $view);
$tpl->assign('form_search', $formSearch->returnForm());

$table = new HTML_Table(array('class' => 'data_table'));
$column = 0;
$row = 0;

$total = '0';
$sumHours = '0';
$numHours = '0';

$field = 'heures-disponibilite-par-semaine';
$extraField = new ExtraFieldValue('user');
$data = $extraField->get_values_by_handler_and_field_variable($userToLoad, $field);
$availableHoursPerWeek = 0;

function dateDiffInWeeks($date1, $date2)
{
    if ($date1 > $date2) {
        return dateDiffInWeeks($date2, $date1);
    }
    $first = new \DateTime($date1);
    $second = new \DateTime($date2);

    return floor($first->diff($second)->days / 7);
}

if ($data) {
    $availableHoursPerWeek = $data['value'];
    $numberWeeks = 0;

    if ($form->validate()) {
        $formData = $form->getSubmitValues();
        if (isset($formData['extra_access_start_date']) && isset($formData['extra_access_end_date'])) {
            $startDate = $formData['extra_access_start_date'];
            $endDate = $formData['extra_access_end_date'];

            $numberWeeks = dateDiffInWeeks($startDate, $endDate);
        }
    }

    $total = $numberWeeks * $availableHoursPerWeek;

    $sessions = SessionManager::getSessionsFollowedByUser($userToLoad);

    if ($sessions) {
        $sessionFieldValue = new ExtraFieldValue('session');

        foreach ($sessions as $session) {
            $sessionId = $session['id'];
            $data = $sessionFieldValue->get_values_by_handler_and_field_variable($sessionId, 'temps-de-travail');
            if ($data) {
                $sumHours += $data['value'];
            }
        }
    }
}

$numHours = $total - $sumHours;
$headers = array(
    "Total d'heures disponibles" => $total,
    'Sommes des heures de sessions inscrites' => $sumHours,
    "Nombre d'heures encore disponible" => $numHours
);
foreach ($headers as $header => $value) {
    $table->setCellContents($row, 0, $header);
    $table->updateCellAttributes($row, 0, 'width="250px"');
    $table->setCellContents($row, 1, $value);
    $row++;
}

$button = '';
if ($userToLoad) {
    $button = Display::url(
        get_lang('OfajEndOfLearnPath'),
        api_get_path(WEB_CODE_PATH).'messages/new_message.php?prefill=ofaj&send_to_user='.$userToLoad,
        ['class' => 'btn btn-default']
    );
    $button .= '<br /><br />';
}

$tpl->assign('grid', $grid.$button.$table->toHtml());
$tpl->assign('grid_js', $griJs);

$content = $tpl->fetch('default/user_portal/search_extra_field.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
