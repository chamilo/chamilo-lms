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

        /**header("Location: ".api_get_self().'?user_id='.$userToLoad);
        break;*/
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

$formSearch->addButtonSearch(get_lang('Search'), 'save');

$form = new FormValidator('search', 'post', api_get_self().'&user_id='.$userToLoad);
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

$form->addButtonSearch(get_lang('Save'), 'save');

$result = SessionManager::getGridColumns('simple');
$columns = $result['columns'];
$column_model = $result['column_model'];
$defaults = [];

if (!empty($items)) {
    /** @var ExtraFieldSavedSearch $item */
    foreach ($items as $item) {
        $variable = 'extra_'.$item->getField()->getVariable();
        $defaults[$variable] = $item->getValue();
    }
}

$form->setDefaults($defaults);

$view = $form->returnForm();
$filterToSend = '';

if ($form->validate()) {
    $params = $form->getSubmitValues();
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

$htmlHeadXtra[] ='
<script>

$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';


if (!empty($filterToSend)) {
    $filterToSend = json_encode($filterToSend);
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&_search=true&_force_search=true&rows=20&page=1&sidx=&sord=asc&filters2='.$filterToSend;
} else {
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&_search=true&_force_search=true&rows=20&page=1&sidx=&sord=asc';
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

$action_links = 'function action_formatter(cellvalue, options, rowObject) {
     return \'<a href="'.api_get_self().'?action=subscribe_user&user_id='.$userToLoad.'&session_id=\'+options.rowId+\'">'.Display::return_icon('add.png', get_lang('Subscribe'),'',ICON_SIZE_SMALL).'</a>'.
    '\';
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
$tpl->assign('grid', $grid);
$tpl->assign('grid_js', $griJs);

$content = $tpl->fetch('default/user_portal/search_extra_field.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
