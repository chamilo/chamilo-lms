<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = WhispeakAuthPlugin::create();

api_protect_admin_script(true);

$plugin->protectTool();

/**
 * Return the wsids for the users in $usersIds.
 *
 * @param array $userIds
 *
 * @return array
 */
function findWsIds(array $userIds)
{
    $wsIds = [];

    foreach ($userIds as $userId) {
        $extraFieldValue = WhispeakAuthPlugin::getAuthUidValue($userId);

        if (!$extraFieldValue) {
            continue;
        }

        $wsId = $extraFieldValue->getValue();

        if (empty($wsId)) {
            continue;
        }

        $wsIds[] = $wsId;
    }

    return $wsIds;
}

/**
 * Group Whispeak results by external_user_id.
 *
 * @param array $results
 *
 * @return array
 */
function groupResults(array $results)
{
    $groups = [];

    foreach ($results as $row) {
        $index = array_shift($row);

        $groups[$index][] = array_values($row);
    }

    return $groups;
}

$form = new FormValidator('frm_filter', 'GET');
$slctUsers = $form->addSelectAjax(
    'users',
    get_lang('Users'),
    [],
    [
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like',
        'id' => 'user_id',
        'multiple' => true,
    ]
);
$form->addDatePicker('date', get_lang('Date'));
$form->addButtonSearch(get_lang('Search'));

$results = [];

if ($form->validate()) {
    $formValues = $form->exportValues();
    $userIds = $formValues['users'] ?: [];
    $date = api_get_utc_datetime($formValues['date'], true, true);

    $wsIds = findWsIds($formValues['users'] ?: []);

    try {
        $results = WhispeakAuthRequest::getUsersInfos($plugin, $wsIds, [], $date);
    } catch (Exception $exception) {
        api_not_allowed(
            true,
            Display::return_message($exception->getMessage(), 'error')
        );
    }
}

$results = groupResults($results);

$pageContent = '';

foreach ($results as $wsId => $activities) {
    $extraFieldValue = new ExtraFieldValue('user');
    $value = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
        WhispeakAuthPlugin::EXTRAFIELD_AUTH_UID,
        $wsId
    );

    if (empty($value)) {
        continue;
    }

    $user = api_get_user_entity($value['item_id']);

    $slctUsers->addOption($user->getCompleteNameWithUsername(), $user->getId());

    $table = new SortableTableFromArray($activities, 3);
    $table->setTotalNumberOfItems(count($activities));
    $table->set_header(
        0,
        $plugin->get_lang('ActivityId'),
        false,
        ['class' => 'text-center'],
        ['class' => 'text-center']
    );
    $table->set_header(
        1,
        $plugin->get_lang('Quality'),
        false,
        ['class' => 'text-center'],
        ['class' => 'text-center']
    );
    $table->set_header(
        2,
        get_lang('Result'),
        false,
        ['class' => 'text-center'],
        ['class' => 'text-center']
    );
    $table->set_header(
        3,
        get_lang('DateTime'),
        true,
        ['class' => 'text-center'],
        ['class' => 'text-center']
    );

    $table->set_column_filter(
        0,
        function ($id) {
            return "<code>$id</code>";
        }
    );
    $table->set_column_filter(
        1,
        function ($quality) use ($plugin) {
            if (empty($quality)) {
                return '';
            }

            $quality = ucfirst($quality);

            return $plugin->get_lang("AudioQuality$quality");
        }
    );
    $table->set_column_filter(
        2,
        function ($result) use ($plugin) {
            return $result
                ? Display::span($plugin->get_lang('Success'), ['class' => 'text-success'])
                : Display::span($plugin->get_lang('Failed'), ['class' => 'text-danger']);
        }
    );
    $table->set_column_filter(
        3,
        function ($date) {
            return api_convert_and_format_date($date, DATE_TIME_FORMAT_LONG_24H);
        }
    );

    $pageContent .= Display::page_header(
        $user->getCompleteNameWithUsername(),
        $wsId
    );
    $pageContent .= $table->return_table();
}

$template = new Template($plugin->get_title());
$template->assign(
    'content',
    $form->returnForm().PHP_EOL.$pageContent
);
$template->display_one_col_template();
