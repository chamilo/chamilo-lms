<?php
/* For licensing terms, see /license.txt */

/**
 * View (MVC patter) for editing a course description.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 */

// protect a course script
api_protect_course_script(true);

if (empty($id)) {
    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : '';
    if (empty($id)) {
        // If the ID was not provided, find the first matching description item given the item type
        $course_description = new CourseDescription();
        $description = $course_description->get_data_by_description_type($description_type);
        if (count($description) > 0) {
            $id = $description['id'];
        }
        // If no corresponding description is found, edit a new one
        unset($course_description);
    }
}
$original_id = $id;

if (empty($error)) {
    $token = Security::get_token();
}
// display categories
$categories = [];
foreach ($default_description_titles as $id => $title) {
    $categories[$id] = $title;
}
$categories[ADD_BLOCK] = get_lang('NewBloc');

$i = 1;
echo '<div class="actions" style="margin-bottom:30px">';
echo '<a href="index.php?'.api_get_cidreq().'">'.
        Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('ToolCourseDescription'), '', ICON_SIZE_MEDIUM).
    '</a>';

ksort($categories);
foreach ($categories as $id => $title) {
    if ($i == ADD_BLOCK) {
        echo '<a href="index.php?'.api_get_cidreq().'&action=add">'.
            Display::return_icon($default_description_icon[$id], $title, '', ICON_SIZE_MEDIUM).'</a>';
        break;
    } else {
        echo '<a href="index.php?action=edit&'.api_get_cidreq().'&description_type='.$id.'">'.
            Display::return_icon($default_description_icon[$id], $title, '', ICON_SIZE_MEDIUM).'</a>';
        $i++;
    }
}
echo '</div>';

// error messages
if (isset($error) && intval($error) == 1) {
    echo Display::return_message(get_lang('FormHasErrorsPleaseComplete'), 'error', false);
}

// default header title form
$description_type = intval($description_type);
$header = $default_description_titles[$description_type];
if ($description_type >= ADD_BLOCK) {
    $header = $default_description_titles[ADD_BLOCK];
}

// display form
$form = new FormValidator(
    'course_description',
    'POST',
    'index.php?action=edit&id='.$original_id.'&description_type='.$description_type.'&'.api_get_cidreq()
);
$form->addElement('header', $header);
$form->addElement('hidden', 'id', $original_id);
$form->addElement('hidden', 'description_type', $description_type);
$form->addElement('hidden', 'sec_token', $token);

if (api_get_configuration_value('save_titles_as_html')) {
    $form->addHtmlEditor(
        'title',
        get_lang('Title'),
        true,
        false,
        ['ToolbarSet' => 'TitleAsHtml']
    );
} else {
    $form->addText('title', get_lang('Title'));
    $form->applyFilter('title', 'html_filter');
}
$form->addHtmlEditor(
    'contentDescription',
    get_lang('Content'),
    true,
    false,
    [
        'ToolbarSet' => 'Basic',
        'Width' => '100%',
        'Height' => '200',
    ]
);
$form->addButtonCreate(get_lang('Save'));

// Set some default values
if (!empty($description_title)) {
    $default['title'] = Security::remove_XSS($description_title);
}
if (!empty($description_content)) {
    $default['contentDescription'] = Security::remove_XSS($description_content, COURSEMANAGERLOWSECURITY);
}
$default['description_type'] = $description_type;

$form->setDefaults($default);

if (isset($question[$description_type])) {
    $message = '<strong>'.get_lang('QuestionPlan').'</strong><br />';
    $message .= $question[$description_type];
    Display::addFlash(Display::return_message($message, 'normal', false));
}
$form->display();
