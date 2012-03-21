<?php
/* For licensing terms, see /license.txt */

/**
	hubert.borderiou 
	Manage tests category page
*/

$htmlHeadXtra[] = '
<script type="text/javascript">
	function confirmDelete(in_txt, in_id) {
		var oldbgcolor = document.getElementById(in_id).style.backgroundColor;
		document.getElementById(in_id).style.backgroundColor="#AAFFB0";
		if (confirm(in_txt)) {
			return true;
		}
		else {
			document.getElementById(in_id).style.backgroundColor = oldbgcolor;
			return false;
		}
	}
</script>
';

// name of the language file that needs to be included
$language_file='exercice';
$nameTools= "";

require_once '../inc/global.inc.php';
require_once 'exercise.lib.php';
require_once 'question.class.php';
require_once 'testcategory.class.php';

$this_section=SECTION_COURSES;

if (!api_is_allowed_to_edit()) {
	api_not_allowed(true);
}

// breadcrumbs
$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('Exercices'));
Display::display_header(get_lang('Category'));

// Action handling: add, edit and remove
if (isset($_GET['action']) && $_GET['action'] == 'addcategory') {
	add_category_form(Security::remove_XSS($_GET['action']));
}
else if (isset($_GET['action']) && $_GET['action'] == 'editcategory') {
	edit_category_form(Security::remove_XSS($_GET['action']));
}
else if (isset($_GET['action']) && $_GET['action'] == 'deletecategory') {
	delete_category_form(Security::remove_XSS($_GET['action']));
}
else {
	display_add_category();
	display_categories();
}

Display::display_footer();

// FUNCTIONS

// form to edit a category
function edit_category_form($in_action) {
	if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
		$category_id = Security::remove_XSS($_GET['category_id']);
		$objcat = new Testcategory($category_id);
		
		// initiate the object		
		$form = new FormValidator('note','post', api_get_self().'?action='.$in_action.'&category_id='.$category_id);
		
		// settting the form elements			
		$form->addElement('header', '', get_lang('EditCategory'));
		$form->addElement('hidden', 'category_id');
		$form->addElement('text', 'category_name', get_lang('CategoryName'),array('size'=>'95'));
		$form->addElement('html_editor', 'category_description', get_lang('CategoryDescription'), null, array('ToolbarSet' => 'test_category', 'Width' => '90%', 'Height' => '200'));
		$form->addElement('style_submit_button', 'SubmitNote', get_lang('ModifyCategory'), 'class="add"');	
		// --------------------
		// setting the defaults
		// --------------------
		$defaults = array();
		$defaults["category_id"] = $objcat->id;
		$defaults["category_name"] = $objcat->name;
		$defaults["category_description"] =  $objcat->description;
		$form->setDefaults($defaults);
		// --------------------
		// setting the rules
		// --------------------
		$form->addRule('category_name', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');	
		// --------------------
		// The validation or display
		// --------------------
		if ($form->validate()) 
		{
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
			display_add_category();
			display_categories();
		} 
		else 
		{
			display_goback();
			$token = Security::get_token();
			$form->addElement('hidden','sec_token');
			$form->setConstants(array('sec_token' => $token));		
			$form->display();
			display_categories();
		}		
	}
	else {
		Display::display_error_message(get_lang('CannotEditCategory'));
	}
}

// process to delete a category
function delete_category_form($in_action) {
	if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
		$category_id = Security::remove_XSS($_GET['category_id']);
		$catobject = new Testcategory($category_id);
		if ($catobject->getCategoryQuestionsNumber() == 0) {
			if ($catobject->removeCategory()) {
				Display::display_confirmation_message(get_lang('DeleteCategoryDone'));
			}
			else {
				Display::display_error_message(get_lang('CannotDeleteCategoryError'));
			}
		}
		else {
			Display::display_error_message(get_lang('CannotDeleteCategory'));
		}
	}
	else {
		Display::display_error_message(get_lang('CannotDeleteCategoryError'));
	}
	display_add_category();
	display_categories();
}

// form to add a category
function add_category_form($in_action) {
	// initiate the object
	$form = new FormValidator('note','post', api_get_self().'?action='.$in_action);
	// settting the form elements	
	$form->addElement('header', '', get_lang('AddACategory'));
	$form->addElement('text', 'category_name', get_lang('CategoryName'),array('size'=>'95'));
	$form->addElement('html_editor', 'category_description', get_lang('CategoryDescription'), null, array('ToolbarSet' => 'test_category', 'Width' => '90%', 'Height' => '200'));
	$form->addElement('style_submit_button', 'SubmitNote', get_lang('AddTestCategory'), 'class="add"');	
	// setting the rules
	$form->addRule('category_name', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');	
	// The validation or display
	if ($form->validate()) 
	{
		$check = Security::check_token('post');	
		if ($check) {
	   		$values = $form->exportValues();
				$v_name = Security::remove_XSS($values['category_name'], COURSEMANAGER);
				$v_description = Security::remove_XSS($values['category_description'], COURSEMANAGER);
	   		$objcat = new Testcategory(0, $v_name, $v_description);
	   		if ($objcat->addCategoryInBDD()) {
	   			Display::display_confirmation_message(get_lang('AddCategoryDone'));
	   		}
	   		else {
	   			Display::display_confirmation_message(get_lang('AddCategoryNameAlreadyExists'));
	   		}
		}
		Security::clear_token();		
		display_add_category();
		display_categories();
	} 
	else 
	{
		display_goback();
		$token = Security::get_token();
		$form->addElement('hidden','sec_token');
		$form->setConstants(array('sec_token' => $token));		
		$form->display();
		display_categories();
	}	
}


// Display add category button

function display_add_category() {
	echo '<div class="actions">';
	echo '<a href="exercice.php?'.api_get_cidreq().'">'.Display::return_icon('back.png', get_lang('GoBackToQuestionList'),'',ICON_SIZE_MEDIUM).'</a>';			
	echo '<a href="'.api_get_self().'?action=addcategory">'.Display::return_icon('question_category.gif', get_lang('AddACategory')).'</a>';
	echo '</div>';
	echo "<br/>";
	echo "<fieldset><legend>".get_lang('QuestionCategory')."</legend></fieldset>";
}


// Display category list

function display_categories() {
    $course_id = api_get_course_int_id();
	$t_cattable = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
	$sql = "SELECT * FROM $t_cattable WHERE c_id = $course_id ORDER BY title";
	$res = Database::query($sql);
	while ($row = Database::fetch_array($res)) {
		// le titre avec le nombre de questions qui sont dans cette cat�gorie
		$tmpobj = new Testcategory($row['id']);
		$nb_question = $tmpobj->getCategoryQuestionsNumber();
		echo '<div class="sectiontitle" id="id_cat'.$row['id'].'">';
        $nb_question_label = $nb_question == 1 ? $nb_question.' '.get_lang('Question') : $nb_question.' '.get_lang('Questions'); 
		echo "<span style='float:right'>".$nb_question_label."</span>";
		echo $row['title'];
		echo '</div>';
		echo '<div class="sectioncomment">';
		echo $row['description'];
		echo '</div>';
		echo '<div>';
		echo '<a href="'.api_get_self().'?action=editcategory&amp;category_id='.$row['id'].'">'.Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL).'</a>';
		if ($nb_question > 0) {
			echo '<a href="javascript:void(0)" onclick="alert(\''.protectJSDialogQuote(get_lang('CannotDeleteCategory')).'\')">';
			echo Display::return_icon('delete_na.png', get_lang('CannotDeleteCategory'), array(), ICON_SIZE_SMALL);
			echo '</a>';
		}
		else {
			$rowname = protectJSDialogQuote($row['title']);
			echo ' <a href="'.api_get_self().'?action=deletecategory&amp;category_id='.$row['id'].'" ';
			echo 'onclick="return confirmDelete(\''.protectJSDialogQuote(get_lang('DeleteCategoryAreYouSure').'['.$rowname).'] ?\', \'id_cat'.$row['id'].'\');">';
			echo Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL).'</a>';
		}
		echo '</div>';
	}
}


// display goback to category list page link
function display_goback() {
	echo '<div class="actions">';
	echo '<a href="'.api_get_self().'">'.Display::return_icon('back.png', get_lang('BackToCategoryList'), array(),  32).'</a>';
	echo '</div>';
}

// To allowed " in javascript dialog box without bad surprises
// replace " with two '
function protectJSDialogQuote($in_txt) {
	$res = $in_txt;
	$res = str_replace("'", "\'", $res);
	$res = str_replace('"', "\'\'", $res);	// super astuce pour afficher les " dans les boite de dialogue
	return $res;
}