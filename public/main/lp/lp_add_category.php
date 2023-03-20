<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

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
    'name' => get_lang('Learning paths'),
];

$form = new FormValidator(
    'lp_add_category',
    'post',
    'lp_controller.php?'.api_get_cidreq()
);

// Form title
$form->addElement('header', null, get_lang('Add learning path category'));

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
$repo = Container::getLpCategoryRepository();
if ($form->validate()) {
    $values = $form->getSubmitValues();
    if (!empty($values['id'])) {
        learnpath::updateCategory($values);
        $url = api_get_self().'?action=list&'.api_get_cidreq();
        Display::addFlash(Display::return_message(get_lang('Update successful')));
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
        $item = $repo->find($id);
        $defaults = [
            'id' => $item->getIid(),
            'name' => $item->getName(),
        ];
        $form->setDefaults($defaults);
    }
}

Display::display_header(get_lang('Create new learning path'), 'Path');

$actions = '<a href="lp_controller.php?'.api_get_cidreq().'">'.
    Display::return_icon(
        'back.png',
        get_lang('ReturnToLearning paths'),
        '',
        ICON_SIZE_MEDIUM
    ).
    '</a>';

echo Display::toolbarAction('toolbar', [$actions]);

$form->display();

Display::display_footer();
