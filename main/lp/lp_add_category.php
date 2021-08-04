<?php

/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com> Adding formvalidator support
 */
$this_section = SECTION_COURSES;
api_protect_course_script();

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

if (!$is_allowed_to_edit) {
    header('location:lp_controller.php?action=list&'.api_get_cidreq());
    exit;
}

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths'),
];

$form = new FormValidator(
    'lp_add_category',
    'post',
    'lp_controller.php?'.api_get_cidreq()
);

// Form title
$form->addElement('header', null, get_lang('AddLPCategory'));

// Title
if (api_get_configuration_value('save_titles_as_html')) {
    $form->addHtmlEditor(
        'name',
        get_lang('Name'),
        true,
        false,
        ['ToolbarSet' => 'TitleAsHtml']
    );
} else {
    $form->addText('name', get_lang('Name'), true);
}

$form->addElement('hidden', 'action', 'add_lp_category');
$form->addElement('hidden', 'c_id', api_get_course_int_id());
$form->addElement('hidden', 'id', 0);

$form->addButtonSave(get_lang('Save'));

if ($form->validate()) {
    $values = $form->getSubmitValues();
    if (!empty($values['id'])) {
        learnpath::updateCategory($values);
        $url = api_get_self().'?action=list&'.api_get_cidreq();
        Display::addFlash(Display::return_message(get_lang('Updated')));
        header('Location: '.$url);
        exit;
    } else {
        learnpath::createCategory($values);
        Display::addFlash(Display::return_message(get_lang('Added')));
        $url = api_get_self().'?action=list&'.api_get_cidreq();
        header('Location: '.$url);
        exit;
    }
} else {
    $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;

    if ($id) {
        $item = learnpath::getCategory($id);
        $defaults = [
            'id' => $item->getId(),
            'name' => $item->getName(),
        ];
        $form->setDefaults($defaults);
    }
}

Display::display_header(get_lang('LearnpathAddLearnpath'), 'Path');

echo '<div class="actions">';
echo '<a href="lp_controller.php?'.api_get_cidreq().'">'.
    Display::return_icon(
        'back.png',
        get_lang('ReturnToLearningPaths'),
        '',
        ICON_SIZE_MEDIUM
    ).
    '</a>';
echo '</div>';

$form->display();

Display::display_footer();
