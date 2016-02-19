<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldSavedSearch;
$cidReset = true;

require_once 'main/inc/global.inc.php';

api_block_anonymous_users();

$userId = api_get_user_id();
$userInfo = api_get_user_info();

$em = Database::getManager();

$form = new FormValidator('search', 'post', api_get_self());
$form->addHeader(get_lang('Diagnosis'));

/** @var ExtraFieldSavedSearch  $saved */
$search = [
    'user' => $userId
];

$items = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findBy($search);

$extraField = new ExtraField('session');
$extraFieldValue = new ExtraFieldValue('session');
$extra = $extraField->addElements($form, '', [], true);

$form->addButtonSave(get_lang('Save'), 'save');
//$form->addButtonSearch(get_lang('Search'), 'search');

$result = SessionManager::getGridColumns('simple');
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

$view = $form->returnForm();
$filterToSend = '';

if ($form->validate()) {
    $params = $form->getSubmitValues();
    if (isset($params['save'])) {
        // save
        foreach ($params as $key => $value) {
            $found = strpos($key, '__persist__');

            if ($found === false) {
                continue;
            }

            $tempKey = str_replace('__persist__', '', $key);
            if (!isset($params[$tempKey])) {
                $params[$tempKey] = array();
            }
        }

        // Parse params.
        foreach ($params as $key => $value) {
            if (substr($key, 0, 6) != 'extra_' && substr($key, 0, 7) != '_extra_') {
                continue;
            }

            $field_variable = substr($key, 6);
            $extraFieldInfo = $extraFieldValue
                ->getExtraField()
                ->get_handler_field_info_by_field_variable($field_variable);

            if (!$extraFieldInfo) {
                continue;
            }

            $user = $em->getRepository('ChamiloUserBundle:User')->find($userId);
            $extraFieldObj = $em->getRepository('ChamiloCoreBundle:ExtraField')->find($extraFieldInfo['id']);

            $search = [
                'field' => $extraFieldObj,
                'user' => $user
            ];

            /** @var ExtraFieldSavedSearch  $saved */
            $saved = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findOneBy($search);

            if ($saved) {
                $saved
                    ->setField($extraFieldObj)
                    ->setUser($user)
                    ->setValue($value)
                ;
                $em->merge($saved);

            } else {
                $saved = new ExtraFieldSavedSearch();
                $saved
                    ->setField($extraFieldObj)
                    ->setUser($user)
                    ->setValue($value)
                ;
                $em->persist($saved);
            }
            $em->flush();
        }

        Display::addFlash(Display::return_message(get_lang('Saved')));
        header("Location: ".api_get_self());
        exit;
    } else {
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
}

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

    $(document).ready( function() {
        '.$jsTag.'

    });
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
        "rules" => $result['rules'],
        /*array(
            array( "field" => "display_start_date", "op" => "gt", "data" => ""),
            array( "field" => "display_end_date", "op" => "gt", "data" => "")
        ),*/
        //'groups' => $groups
    )
);

$action_links = 'function action_formatter(cellvalue, options, rowObject) {
     return \'<a href="session_edit.php?page=resume_session.php&id=\'+options.rowId+\'">'.Display::return_icon('edit.png',get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>'.
    '\';
}';

$htmlHeadXtra[] = api_get_jqgrid_js();

/*$griJs = Display::grid_js('sessions', $url, $columns, $column_model, $extra_params, array(), $action_links, true);

$grid = '<div id="session-table" class="table-responsive">';
$grid .= Display::grid_html('sessions');
$grid .= '</div>';*/

$tpl = new Template(get_lang('Diagnosis'));
$tpl->assign('form', $view);
//$tpl->assign('grid', $grid);
//$tpl->assign('grid_js', $griJs);
$content = $tpl->fetch('default/user_portal/search_extra_field.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
