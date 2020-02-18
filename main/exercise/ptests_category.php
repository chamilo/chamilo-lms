<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

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

$nameTools = '';

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

api_protect_course_script(true);

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$category = new PTestCategory();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$exerciseId = (int) $_GET['exerciseId'];

// get from session
$objExercise = Session::read('objExercise');

// Exercise object creation.
if (!is_object($objExercise) || $objExercise->selectPtType() != EXERCISE_PT_TYPE_PTEST) {
    // construction of the Exercise object
    $objExercise = new Exercise();

    // creation of a new exercise if wrong or not specified exercise ID
    if ($exerciseId) {
        $parseQuestionList = $showPagination > 0 ? false : true;
        if ($editQuestion) {
            $parseQuestionList = false;
            $showPagination = true;
        }
        $objExercise->read($exerciseId, $parseQuestionList);
    }
    // saves the object into the session
    Session::write('objExercise', $objExercise);
}

// Exercise can be edited in their course.
if ($objExercise->sessionId != $sessionId) {
    api_not_allowed(true);
}

// breadcrumbs
$interbreadcrumb[] = [
    "url" => "ptest_admin.php?exercise_id=".$objExercise->selectId()."&".api_get_cidreq(),
    "name" => get_lang('Exercises'),
];

$action = isset($_GET['action']) ? $_GET['action'] : '';
$content = '';

switch ($action) {
    case 'addcategory':
        $content = add_category_form('addcategory');
        break;
    case 'editcategory':
        $content = edit_category_form('editcategory');
        break;
    case 'deletecategory':
        delete_category_form();
        break;
}

Display::display_header(get_lang('Category'));
displayActionBar();
echo $content;
echo $category->displayCategories($exerciseId, $courseId, $sessionId);
Display::display_footer();

/**
 * Form to edit a category.
 *
 * @todo move to PTestCategory.class.php
 *
 * @param string $action
 */
function edit_category_form($action)
{
    $exerciseId = (int) $_GET['exerciseId'];
    $action = Security::remove_XSS($action);
    if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
        $categoryId = intval($_GET['category_id']);
        $objcat = new PTestCategory();
        $objcat = $objcat->getCategory($categoryId);

        $params = [
            'exerciseId' => $exerciseId,
            'action' => $action,
            'category_id' => $categoryId,
        ];
        $form = new FormValidator(
            'note',
            'post',
            api_get_self().'?'.http_build_query($params).'&'.api_get_cidreq()
        );

        // Setting the form elements
        $form->addElement('header', get_lang('EditCategory'));
        $form->addElement('hidden', 'category_id');
        $form->addElement('text', 'category_name', get_lang('PtestCategoryName'), ['size' => '95']);
        $form->addElement('color', 'category_color', get_lang('PtestCategoryColor'), ['size' => '95']);
        $form->addElement('number', 'category_position', get_lang('PtestCategoryPosition'), ['size' => '95']);
        $form->addHtmlEditor(
            'category_description',
            get_lang('PtestCategoryDescription'),
            false,
            false,
            ['ToolbarSet' => 'TestQuestionDescription', 'Height' => '200']
        );
        $form->addButtonCreate(get_lang('ModifyPTestFeature'), 'SubmitNote');

        // setting the rules
        $form->addRule('category_name', get_lang('ThisFieldIsRequired'), 'required');

        // setting the defaults
        $defaults = [];
        $defaults['category_id'] = $objcat->id;
        $defaults['category_name'] = $objcat->name;
        $defaults['category_color'] = $objcat->color;
        $defaults['category_position'] = $objcat->position;
        $defaults['category_description'] = $objcat->description;
        $form->setDefaults($defaults);

        // The validation or display
        if ($form->validate()) {
            $check = Security::check_token('post');
            if ($check) {
                $values = $form->exportValues();
                $category = new PTestCategory();
                $category = $category->getCategory($values['category_id']);

                if ($category) {
                    $category->name = $values['category_name'];
                    $category->description = $values['category_description'];
                    $category->color = $values['category_color'];
                    $category->position = $values['category_position'];
                    $category->modifyCategory();
                    Display::addFlash(Display::return_message(get_lang('Updated')));
                } else {
                    Display::addFlash(Display::return_message(get_lang('ModifyCategoryError'), 'error'));
                }
            }
            Security::clear_token();
        } else {
            $token = Security::get_token();
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);

            return $form->returnForm();
        }
    } else {
        Display::addFlash(
            Display::return_message(get_lang('CannotEditCategory'), 'error')
        );
    }
}

// process to delete a category
function delete_category_form()
{
    if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
        $category = new PTestCategory();
        if ($category->removeCategory($_GET['category_id'])) {
            Display::addFlash(Display::return_message(get_lang('DeleteCategoryDone')));
        } else {
            Display::addFlash(Display::return_message(get_lang('CannotDeleteCategoryError'), 'error'));
        }
    } else {
        Display::addFlash(Display::return_message(get_lang('CannotDeleteCategoryError'), 'error'));
    }
}

/**
 * form to add a category.
 *
 * @todo move to PTestCategory.class.php
 *
 * @param string $action
 */
function add_category_form($action)
{
    $exerciseId = (int) $_GET['exerciseId'];
    $action = Security::remove_XSS($action);
    // initiate the object
    $form = new FormValidator(
        'note',
        'post',
        api_get_self().'?'.http_build_query([
            'exerciseId' => $exerciseId,
            'action' => $action,
        ]).'&'.api_get_cidreq()
    );
    // Setting the form elements
    $form->addElement('header', get_lang('AddACategory'));
    $form->addElement('text', 'category_name', get_lang('PtestCategoryName'), ['size' => '95']);
    $form->addElement('color', 'category_color', get_lang('PtestCategoryColor'), ['size' => '95']);
    $form->addElement('number', 'category_position', get_lang('PtestCategoryPosition'), ['size' => '95']);
    $form->addHtmlEditor(
        'category_description',
        get_lang('PtestCategoryDescription'),
        false,
        false,
        ['ToolbarSet' => 'TestQuestionDescription', 'Height' => '200']
    );
    $form->addButtonCreate(get_lang('AddPTestFeature'), 'SubmitNote');
    // setting the rules
    $form->addRule('category_name', get_lang('ThisFieldIsRequired'), 'required');
    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            $category = new PTestCategory();
            $category->name = $values['category_name'];
            $category->description = $values['category_description'];
            $category->color = $values['category_color'];
            $category->position = $values['category_position'];
            if ($category->save($exerciseId)) {
                Display::addFlash(Display::return_message(get_lang('AddCategoryDone')));
            } else {
                Display::addFlash(Display::return_message(get_lang('AddCategoryNameAlreadyExists'), 'warning'));
            }
        }
        Security::clear_token();
    } else {
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);

        return $form->returnForm();
    }
}

// Display add category button
function displayActionBar()
{
    $exerciseId = (int) $_GET['exerciseId'];
    echo '<div class="actions">';
    $urlParams = 'exerciseId='.$exerciseId.'&'.api_get_cidreq();
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/ptest_admin.php?'.$urlParams.'">'.
        Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).
        '</a>';

    echo '<a href="'.api_get_self().'?exerciseId='.$exerciseId.'&action=addcategory&'.api_get_cidreq().'">'.
        Display::return_icon('new_folder.png', get_lang('AddACategory'), null, ICON_SIZE_MEDIUM).
        '</a>';

    echo '</div>';
    echo "<br/>";
    echo "<fieldset><legend>".get_lang('PtestCategoryList')."</legend></fieldset>";
}
