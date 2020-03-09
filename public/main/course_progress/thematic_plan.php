<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * View (MVC patter) for thematic plan.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 */
$tpl = new Template(get_lang('Thematic control'));
$toolbar = null;
$formLayout = null;

// actions menu
$new_thematic_plan_data = [];
if (!empty($thematic_plan_data)) {
    foreach ($thematic_plan_data as $thematic_item) {
        $thematic_simple_list[] = $thematic_item['description_type'];
        $new_thematic_plan_data[$thematic_item['description_type']] = $thematic_item;
    }
}

$new_id = ADD_THEMATIC_PLAN;
if (!empty($thematic_simple_list)) {
    foreach ($thematic_simple_list as $item) {
        if ($item >= ADD_THEMATIC_PLAN) {
            $new_id = $item + 1;
            $default_thematic_plan_title[$item] = $new_thematic_plan_data[$item]['title'];
        }
    }
}

if (isset($message) && 'ok' == $message) {
    echo Display::return_message(get_lang('Thematic section has been created successfully'), 'normal');
}

if ('thematic_plan_list' === $action) {
    $token = Security::get_token();
    Session::write('thematic_plan_token', $token);
    $form = new FormValidator(
        'thematic_plan_add',
        'POST',
        'index.php?action=thematic_plan_list&thematic_id='.$thematic_id.'&'.api_get_cidreq()
    );
    $form->addElement('hidden', 'action', 'thematic_plan_add');
    $form->addElement('hidden', 'thematic_plan_token', $token);
    $form->addElement('hidden', 'thematic_id', $thematic_id);

    foreach ($default_thematic_plan_title as $id => $title) {
        $btnDelete = Display::toolbarButton(
            get_lang('Delete'),
            '#',
            'times',
            'danger',
            ['role' => 'button', 'data-id' => $id, 'class' => 'btn-delete']
        );

        $form->addElement('hidden', 'description_type['.$id.']', $id);
        $form->addText("title[$id]", [get_lang('Title'), null, $btnDelete], false);
        $form->addHtmlEditor(
            'description['.$id.']',
            get_lang('Description'),
            false,
            false,
            [
                'ToolbarStartExpanded' => 'false',
                'ToolbarSet' => 'Basic',
                'Height' => '150',
            ]
        );

        if (!empty($thematic_simple_list) && in_array($id, $thematic_simple_list)) {
            $thematic_plan = $new_thematic_plan_data[$id];
            // set default values
            $default['title['.$id.']'] = $thematic_plan['title'];
            $default['description['.$id.']'] = $thematic_plan['description'];
            $thematic_plan = null;
        } else {
            $thematic_plan = null;
            $default['title['.$id.']'] = $title;
            $default['description['.$id.']'] = '';
        }
        $form->setDefaults($default);
    }
    $form->addGroup([
        $form->addButton(
            'add_item',
            get_lang('Save and add new item'),
            'plus',
            'info',
            'default',
            null,
            [],
            true
        ),
        $form->addButtonSave(get_lang('Save'), 'submit', true),
    ]);
    $formLayout = $form->returnForm();
} elseif ('thematic_plan_add' == $action || 'thematic_plan_edit' == $action) {

}



$content = $tpl->fetch($thematicLayout);
$tpl->assign('content', $content);

$tpl->display_one_col_template();
