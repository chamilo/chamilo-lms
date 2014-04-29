<?php
/* For licensing terms, see /license.txt */

/**
  hubert.borderiou
  Manage tests category page
 */
$htmlHeadXtra[] = '
<script>
	function confirmDelete(in_txt, in_id) {
		var oldbgcolor = document.getElementById(in_id).style.backgroundColor;
		document.getElementById(in_id).style.backgroundColor="#AAFFB0";
		if (confirm(in_txt)) {
			return true;
		} else {
			document.getElementById(in_id).style.backgroundColor = oldbgcolor;
			return false;
		}
	}
</script>';

// name of the language file that needs to be included
$language_file = 'exercice';
$nameTools = "";

require_once '../inc/global.inc.php';
require_once 'exercise.lib.php';
require_once 'question.class.php';
require_once 'testcategory.class.php';

$this_section = SECTION_COURSES;

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}
$category = new Testcategory();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();

// breadcrumbs
$interbreadcrumb[] = array("url" => "exercice.php", "name" => get_lang('Exercices'));
Display::display_header(get_lang('Category'));

// Action handling: add, edit and remove
if (isset($_GET['action']) && $_GET['action'] == 'addcategory') {
    add_category_form($_GET['action']);
    display_add_category();
} else if (isset($_GET['action']) && $_GET['action'] == 'editcategory') {
    edit_category_form($_GET['action']);
} else if (isset($_GET['action']) && $_GET['action'] == 'deletecategory') {
    delete_category_form($_GET['action']);
    display_add_category();
} else {
    display_add_category();
}
echo $category->displayCategories($courseId, $sessionId);

Display::display_footer();

// FUNCTIONS
// form to edit a category
/**
 * @todo move to testcategory.class.php
 * @param string $in_action
 */
function edit_category_form($in_action) {
    $in_action = Security::remove_XSS($in_action);
    if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
        $category_id = Security::remove_XSS($_GET['category_id']);
        $objcat = new Testcategory($category_id);

        // initiate the object
        $form = new FormValidator('note', 'post', api_get_self() . '?action=' . $in_action . '&category_id=' . $category_id);

        // settting the form elements
        $form->addElement('header', get_lang('EditCategory'));
        $form->addElement('hidden', 'category_id');
        $form->addElement('text', 'category_name', get_lang('CategoryName'), array('size' => '95'));
        $form->add_html_editor('category_description', get_lang('CategoryDescription'), false, false, array('ToolbarSet' => 'test_category', 'Width' => '90%', 'Height' => '200'));
        $form->addElement('style_submit_button', 'SubmitNote', get_lang('ModifyCategory'), 'class="add"');

        // setting the defaults
        $defaults = array();
        $defaults["category_id"] = $objcat->id;
        $defaults["category_name"] = $objcat->name;
        $defaults["category_description"] = $objcat->description;
        $form->setDefaults($defaults);

        // setting the rules
        $form->addRule('category_name', get_lang('ThisFieldIsRequired'), 'required');

        // The validation or display
        if ($form->validate()) {
            $check = Security::check_token('post');
            if ($check) {
                $values = $form->exportValues();
                $v_id = Security::remove_XSS($values['category_id']);
                $v_name = Security::remove_XSS($values['category_name'], COURSEMANAGER);
                $v_description = Security::remove_XSS($values['category_description'], COURSEMANAGER);
                $objcat = new Testcategory($v_id, $v_name, $v_description);
                if ($objcat->modifyCategory()) {
                    Display::display_confirmation_message(get_lang('MofidfyCategoryDone'));
                } else {
                    Display::display_confirmation_message(get_lang('ModifyCategoryError'));
                }
            }
            Security::clear_token();
        } else {
            display_goback();
            $token = Security::get_token();
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(array('sec_token' => $token));
            $form->display();
        }
    } else {
        Display::display_error_message(get_lang('CannotEditCategory'));
    }
}

// process to delete a category
function delete_category_form($in_action) {
    if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
        $category_id = Security::remove_XSS($_GET['category_id']);
        $catobject = new Testcategory($category_id);
        if ($catobject->removeCategory()) {
            Display::display_confirmation_message(get_lang('DeleteCategoryDone'));
        } else {
            Display::display_error_message(get_lang('CannotDeleteCategoryError'));
        }
    } else {
        Display::display_error_message(get_lang('CannotDeleteCategoryError'));
    }
}

/**
 * form to add a category
 * @todo move to testcategory.class.php
 * @param string $in_action
 */
function add_category_form($in_action) {
    $in_action = Security::remove_XSS($in_action);
    // initiate the object
    $form = new FormValidator('note', 'post', api_get_self() . '?action=' . $in_action);
    // Setting the form elements
    $form->addElement('header', get_lang('AddACategory'));
    $form->addElement('text', 'category_name', get_lang('CategoryName'), array('size' => '95'));
    $form->add_html_editor('category_description', get_lang('CategoryDescription'), false, false, array('ToolbarSet' => 'test_category', 'Width' => '90%', 'Height' => '200'));
    $form->addElement('style_submit_button', 'SubmitNote', get_lang('AddTestCategory'), 'class="add"');
    // setting the rules
    $form->addRule('category_name', get_lang('ThisFieldIsRequired'), 'required');
    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            $v_name = Security::remove_XSS($values['category_name'], COURSEMANAGER);
            $v_description = Security::remove_XSS($values['category_description'], COURSEMANAGER);
            $objcat = new Testcategory(0, $v_name, $v_description);
            if ($objcat->addCategoryInBDD()) {
                Display::display_confirmation_message(get_lang('AddCategoryDone'));
            } else {
                Display::display_confirmation_message(get_lang('AddCategoryNameAlreadyExists'));
            }
        }
        Security::clear_token();
    } else {
        display_goback();
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(array('sec_token' => $token));
        $form->display();
    }
}

// Display add category button

function display_add_category() {
    echo '<div class="actions">';
    echo '<a href="exercice.php?' . api_get_cidreq() . '">' . Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM) . '</a>';
    echo '<a href="' . api_get_self() . '?action=addcategory">' . Display::return_icon('question_category.gif', get_lang('AddACategory')) . '</a>';
    echo '</div>';
    echo "<br/>";
    echo "<fieldset><legend>" . get_lang('QuestionCategory') . "</legend></fieldset>";
}

// display goback to category list page link
function display_goback() {
    echo '<div class="actions">';
    echo '<a href="' . api_get_self() . '">' . Display::return_icon('back.png', get_lang('BackToCategoryList'), array(), 32) . '</a>';
    echo '</div>';
}
