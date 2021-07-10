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

$nameTools = '';

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

api_protect_course_script(true);

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$category = new TestCategory();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();

// breadcrumbs
$interbreadcrumb[] = [
    "url" => "exercise.php?".api_get_cidreq(),
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
    case 'export_category':
        $archiveFile = 'export_exercise_categories_'.api_get_course_id().'_'.api_get_local_time();
        $categories = $category->getCategories($courseId, $sessionId);
        $export = [];
        $export[] = ['title', 'description'];

        if (!empty($categories)) {
            foreach ($categories as $category) {
                $export[] = [$category['title'], $category['description']];
            }
        }

        Export::arrayToCsv($export, $archiveFile);
        exit;
        break;
    case 'import_category':
        $form = importCategoryForm();
        if ($form->validate()) {
            $categories = Import::csv_reader($_FILES['file']['tmp_name']);
            if (!empty($categories)) {
                foreach ($categories as $item) {
                    $cat = new TestCategory();
                    $cat->name = $item['title'];
                    $cat->description = $item['description'];
                    $cat->save();
                }
                Display::addFlash(Display::return_message(get_lang('Imported')));
            }
        }
        $content = $form->returnForm();
        break;
}

Display::display_header(get_lang('Category'));
displayActionBar();
echo $content;
echo $category->displayCategories($courseId, $sessionId);
Display::display_footer();

/**
 * @return FormValidator
 */
function importCategoryForm()
{
    $form = new FormValidator('import', 'post', api_get_self().'?action=import_category&'.api_get_cidreq());
    $form->addElement('file', 'file', get_lang('ImportCSVFileLocation'));
    $form->addRule('file', get_lang('ThisFieldIsRequired'), 'required');
    $form->addButtonImport(get_lang('Import'));

    return $form;
}

/**
 * Form to edit a category.
 *
 * @todo move to TestCategory.class.php
 *
 * @param string $action
 */
function edit_category_form($action)
{
    $action = Security::remove_XSS($action);
    if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
        $category_id = intval($_GET['category_id']);
        $objcat = new TestCategory();
        $objcat = $objcat->getCategory($category_id);
        $form = new FormValidator(
            'note',
            'post',
            api_get_self().'?action='.$action.'&category_id='.$category_id.'&'.api_get_cidreq()
        );

        // Setting the form elements
        $form->addElement('header', get_lang('EditCategory'));
        $form->addElement('hidden', 'category_id');
        $form->addElement('text', 'category_name', get_lang('CategoryName'), ['size' => '95']);
        $form->addHtmlEditor(
            'category_description',
            get_lang('CategoryDescription'),
            false,
            false,
            ['ToolbarSet' => 'TestQuestionDescription', 'Height' => '200']
        );
        $form->addButtonSave(get_lang('ModifyCategory'), 'SubmitNote');

        // setting the defaults
        $defaults = [];
        $defaults['category_id'] = $objcat->iid;
        $defaults['category_name'] = $objcat->name;
        $defaults['category_description'] = $objcat->description;
        $form->setDefaults($defaults);

        // setting the rules
        $form->addRule('category_name', get_lang('ThisFieldIsRequired'), 'required');

        // The validation or display
        if ($form->validate()) {
            $check = Security::check_token('post');
            if ($check) {
                $values = $form->exportValues();
                $category = new TestCategory();
                $category = $category->getCategory($values['category_id']);

                if ($category) {
                    $category->name = $values['category_name'];
                    $category->description = $values['category_description'];
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
        $category = new TestCategory();
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
 * @todo move to TestCategory.class.php
 *
 * @param string $action
 */
function add_category_form($action)
{
    $action = Security::remove_XSS($action);
    // initiate the object
    $form = new FormValidator('note', 'post', api_get_self().'?action='.$action.'&'.api_get_cidreq());
    // Setting the form elements
    $form->addElement('header', get_lang('AddACategory'));
    $form->addElement('text', 'category_name', get_lang('CategoryName'), ['size' => '95']);
    $form->addHtmlEditor(
        'category_description',
        get_lang('CategoryDescription'),
        false,
        false,
        ['ToolbarSet' => 'TestQuestionDescription', 'Height' => '200']
    );
    $form->addButtonCreate(get_lang('AddTestCategory'), 'SubmitNote');
    // setting the rules
    $form->addRule('category_name', get_lang('ThisFieldIsRequired'), 'required');
    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            $category = new TestCategory();
            $category->name = $values['category_name'];
            $category->description = $values['category_description'];
            if ($category->save()) {
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
    echo '<div class="actions">';
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq().'">'.
            Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).'</a>';

    echo '<a href="'.api_get_self().'?action=addcategory&'.api_get_cidreq().'">'.
        Display::return_icon('new_folder.png', get_lang('AddACategory'), null, ICON_SIZE_MEDIUM).'</a>';

    echo Display::url(
        Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), [], ICON_SIZE_MEDIUM),
        api_get_self().'?action=export_category&'.api_get_cidreq()
    );

    echo Display::url(
        Display::return_icon('import_csv.png', get_lang('ImportAsCSV'), [], ICON_SIZE_MEDIUM),
        api_get_self().'?action=import_category&'.api_get_cidreq()
    );

    echo '</div>';
    echo "<br/>";
    echo "<fieldset><legend>".get_lang('QuestionCategory')."</legend></fieldset>";
}
