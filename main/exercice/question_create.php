<?php // $Id: question_create.php 20569 2009-05-12 21:34:00Z pcool $
 
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/


// name of the language file that needs to be included
$language_file='exercice';

// including global Dokeos file
include("../inc/global.inc.php");

// including additional libraries
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once ('question.class.php');


// the section (tabs)
$this_section=SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

// breadcrumbs
$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('Exercices'));

// Tool name
$nameTools=get_lang('AddQuestionToExercise');



// The form
$form = new FormValidator('add_question','post');
// form title
$form->addElement('header','',get_lang('AddQuestionToExercise'));

// the question types (normal form element)
/*
foreach (Question::$questionTypes as $key=>$value)
{
	$question_types[] = get_lang($value[1]);
}
$form->addElement('select', 'question_type', get_lang('QuestionType'), $question_types);
*/


// the question types (jquery form element)
$form->addElement('hidden', 'question_type_hidden', get_lang('QuestionType'), array('id'=>'question_type_hidden'));
$form->addElement('static','','<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript"></script>
							<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.customselect.js" type="text/javascript"></script>');
$form->addElement('static','select_question_type', get_lang('QuestionType'),'<div id="questiontypes"></div>');

// the exercices
$tbl_exercices = Database :: get_course_table(TABLE_QUIZ_TEST);
$sql = "SELECT id,title,type,description, results_disabled FROM $tbl_exercices WHERE active<>'-1' ORDER BY title ASC";
$result = api_sql_query($sql, __FILE__, __LINE__);
$exercises['-'] = '-'.get_lang('SelectExercice').'-';
while ($row = Database :: fetch_array($result)) 
{
	$exercises[$row['id']] = $row['title'];	
}
$form->addElement('select', 'exercice', get_lang('Exercice'), $exercises);

// the submit button
$form->addElement('style_submit_button', 'SubmitCreateQuestion', get_lang('CreateQuestion'), 'class="add"');

// setting the rules
// $form->addRule('question_type', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('exercice', '<span class="required">'.get_lang('ThisFieldIsRequired').'</span>', 'required');
$form->addRule('exercice', '<span class="required">'.get_lang('YouHaveToSelectATest').'</span>', 'numeric');
$form->registerRule('validquestiontype', 'callback', 'check_question_type');
$form->addRule('question_type_hidden', get_lang('InvalidQuestionType'), 'validquestiontype');



if ($form->validate())
{
	$values = $form->exportValues();
	//echo 'form validates';
	//print_r($values);
	
	foreach (Question::$questionTypes as $question_type_id => $question_type_class_and_name)
	{
		if (get_lang($question_type_class_and_name[1]) == $values['question_type_hidden'])
		{
			$answer_type = $question_type_id;
		}
	}
	
	header('Location: admin.php?exerciseId='.$values['exercice'].'&newQuestion=yes&answerType='.$answer_type);
}
else 
{
	// header
	Display::display_header($nameTools);	
	
	// displaying the form
	$form->display();
	
	// footer
	Display::display_footer();	
}


?>



<script>
var ddlObj1=$("#questiontypes").finalselect({id:"test",viewWidth:'260px', viewHeight:'150px', selectText:'<?php echo Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle; cursor:hand'))."&nbsp;&nbsp;".get_lang('SelectQuestionType');?>',selectImage:'<?php echo api_get_path(WEB_IMG_PATH); ?>select.png', viewMouseoverColor: '#EFEFEF'});
$("#test-select").bind('click',function(){
	$("#question_type_hidden").val(ddlObj1.getText());
});



<?php 
// defining the pictures of the question types
$pictures_question_types[1] = 'mcua.gif';
$pictures_question_types[2] = 'mcma.gif';
$pictures_question_types[3] = 'fill_in_blanks.gif';
$pictures_question_types[4] = 'matching.gif';
$pictures_question_types[5] = 'open_answer.gif';
$pictures_question_types[6] = 'hotspot.gif';

foreach (Question::$questionTypes as $key=>$value)
{
	?>
	ddlObj1.addItem('<table width="100%"><tr><td style="width: 37px;" valign="top"><?php Display::display_icon($pictures_question_types[$key],addslashes(get_lang($value[1])),array('height'=>'40px;', 'style' => 'vertical-align:top; cursor:hand;')); ?></td><td><span class="thistext" style="cursor:hand"><?php echo addslashes(get_lang($value[1])); ?></span><br/><sub><?php /*echo addslashes(get_lang($value[1].'Comment'));*/ ?></sub></td></tr></table>','');
	<?php
}
?>
</script>



<?php
function check_question_type($parameter)
{
	foreach (Question::$questionTypes as $key=>$value)
	{
		$valid_question_types[] = get_lang($value[1]);
		//$valid_question_types[] = trim($value[1]);
	}
	if (in_array($parameter, $valid_question_types))
	{
		return true;
	}
	else 
	{
		return false;
	}
}

?>