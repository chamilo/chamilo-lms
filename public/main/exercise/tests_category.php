<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ActionIcon;

require_once __DIR__.'/../inc/global.inc.php';

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
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Tests'),
];

$action = $_GET['action'] ?? '';
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
                $export[] = [$category->getTitle(), $category->getDescription()];
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
    $form->addElement('file', 'file', get_lang('CSV file import location'));
    $form->addRule('file', get_lang('Required field'), 'required');
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
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $category_id = (int) $_GET['id'];
        $objcat = new TestCategory();
        $objcat = $objcat->getCategory($category_id);
        $form = new FormValidator(
            'note',
            'post',
            api_get_self().'?action='.$action.'&id='.$category_id.'&'.api_get_cidreq()
        );

        // Setting the form elements
        $form->addElement('header', get_lang('Edit this category'));
        $form->addElement('hidden', 'id');
        $form->addElement('text', 'category_name', get_lang('Category name'), ['size' => '95']);
        $form->addHtmlEditor(
            'category_description',
            get_lang('Category description'),
            false,
            false,
            ['ToolbarSet' => 'TestQuestionDescription', 'Height' => '200']
        );
        $form->addButtonSave(get_lang('Edit category'), 'SubmitNote');

        // setting the defaults
        $defaults = [];
        $defaults['id'] = $objcat->id;
        $defaults['category_name'] = $objcat->name;
        $defaults['category_description'] = $objcat->description;
        $form->setDefaults($defaults);

        // setting the rules
        $form->addRule('category_name', get_lang('Required field'), 'required');

        // The validation or display
        if ($form->validate()) {
            $check = Security::check_token('post');

            if ($check) {
                $values = $form->exportValues();
                $category = new TestCategory();
                $category = $category->getCategory($values['id']);

                if ($category) {
                    $category->name = $values['category_name'];
                    $category->description = $values['category_description'];
                    $category->modifyCategory();
                    Display::addFlash(Display::return_message(get_lang('Update successful')));
                } else {
                    Display::addFlash(Display::return_message(get_lang('Edit categoryError'), 'error'));
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
            Display::return_message(get_lang('Could not edit category'), 'error')
        );
    }
}

// process to delete a category
function delete_category_form()
{
    if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
        $category = new TestCategory();
        if ($category->removeCategory($_GET['category_id'])) {
            Display::addFlash(Display::return_message(get_lang('Category deleted')));
        } else {
            Display::addFlash(Display::return_message(get_lang('Error: could not delete category'), 'error'));
        }
    } else {
        Display::addFlash(Display::return_message(get_lang('Error: could not delete category'), 'error'));
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
    $form->addElement('header', get_lang('Add category'));
    $form->addElement('text', 'category_name', get_lang('Category name'), ['size' => '95']);
    $form->addHtmlEditor(
        'category_description',
        get_lang('Category description'),
        false,
        false,
        ['ToolbarSet' => 'TestQuestionDescription', 'Height' => '200']
    );
    $form->addButtonCreate(get_lang('Add test category'), 'SubmitNote');
    // setting the rules
    $form->addRule('category_name', get_lang('Required field'), 'required');
    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            $category = new TestCategory();
            $category->name = $values['category_name'];
            $category->description = $values['category_description'];
            if ($category->save()) {
                Display::addFlash(Display::return_message(get_lang('Category added')));
            } else {
                Display::addFlash(Display::return_message(get_lang('Already exists'), 'warning'));
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
    $actions = '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq().'">'.
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Go back to the questions list')).'</a>';

    $actions .= '<a href="'.api_get_self().'?action=addcategory&'.api_get_cidreq().'">'.
        Display::getMdiIcon(ActionIcon::CREATE_FOLDER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add category')).'</a>';

    $actions .= Display::url(
        Display::getMdiIcon(ActionIcon::EXPORT_CSV, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('CSV export')),
        api_get_self().'?action=export_category&'.api_get_cidreq()
    );

    $actions .= Display::url(
        Display::getMdiIcon(ActionIcon::IMPORT_ARCHIVE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Import from a CSV')),
        api_get_self().'?action=import_category&'.api_get_cidreq()
    );

    echo Display::toolbarAction('toolbar', [$actions]);
    echo Display::page_header(get_lang('Questions category'));
}
