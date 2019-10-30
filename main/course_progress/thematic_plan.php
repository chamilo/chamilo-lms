<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * View (MVC patter) for thematic plan.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 *
 * @package chamilo.course_progress
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

if (isset($message) && $message == 'ok') {
    echo Display::return_message(get_lang('Thematic section has been created successfully'), 'normal');
}

if ($action === 'thematic_plan_list') {
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
} elseif ($action == 'thematic_plan_add' || $action == 'thematic_plan_edit') {
    if ($description_type >= ADD_THEMATIC_PLAN) {
        $header_form = get_lang('Other');
    } else {
        $header_form = $default_thematic_plan_title[$description_type];
    }
    if (!$error) {
        $token = md5(uniqid(rand(), true));
        Session::write('thematic_plan_token', $token);
    }

    // display form
    $form = new FormValidator(
        'thematic_plan_add',
        'POST',
        'index.php?action=thematic_plan_edit&thematic_id='.$thematic_id.'&'.api_get_cidreq(),
        '',
        'style="width: 100%;"'
    );
    $form->addElement('hidden', 'action', $action);
    $form->addElement('hidden', 'thematic_plan_token', $token);

    if (!empty($thematic_id)) {
        $form->addElement('hidden', 'thematic_id', $thematic_id);
    }
    if (!empty($description_type)) {
        $form->addElement('hidden', 'description_type', $description_type);
    }

    $form->addText('title', get_lang('Title'), true, ['size' => '50']);
    $form->addHtmlEditor(
        'description',
        get_lang('Description'),
        false,
        false,
        [
            'ToolbarStartExpanded' => 'false',
            'ToolbarSet' => 'Basic',
            'Width' => '80%',
            'Height' => '150',
        ]
    );
    $form->addButtonSave(get_lang('Save'));

    if ($description_type < ADD_THEMATIC_PLAN) {
        $default['title'] = $default_thematic_plan_title[$description_type];
    }
    if (!empty($thematic_plan_data)) {
        // set default values
        $default['title'] = $thematic_plan_data[0]['title'];
        $default['description'] = $thematic_plan_data[0]['description'];
    }
    $form->setDefaults($default);

    if (isset($default_thematic_plan_question[$description_type])) {
        $message = '<strong>'.get_lang('Help').'</strong><br />';
        $message .= $default_thematic_plan_question[$description_type];
        Display::addFlash(Display::return_message($message, 'normal', false));
    }

    // error messages
    if ($error) {
        Display::addFlash(
            Display::return_message(
                get_lang('The form contains incorrect or incomplete data. Please check your input.'),
                'error',
                false
            )
        );
    }
    $formLayout = $form->returnForm();
}
$tpl->assign('title_thematic', $thematic_data['title']);
$tpl->assign('content_thematic', $thematic_data['content']);
$tpl->assign('form_thematic', $formLayout);
$thematicLayout = $tpl->get_template('course_progress/thematic_plan.tpl');
$content = $tpl->fetch($thematicLayout);
$tpl->assign('content', $content);

$tpl->display_one_col_template();
