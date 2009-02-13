<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/


/**
*	Feedback
*	@package dokeos.exercise
* 	@author
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/
include('exercise.class.php');
include('question.class.php');
include('answer.class.php');

include('exercise.lib.php');

// name of the language file that needs to be included
$language_file='exercice';

include("../inc/global.inc.php");
$this_section=SECTION_COURSES;

include_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
include_once(api_get_path(LIBRARY_PATH).'document.lib.php');
include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
$nameTools=get_lang('ExerciseManagement');
$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('Exercices'));
Display::display_header($nameTools,"Exercise");
?>
<table border="0" align="center" cellpadding="2" cellspacing="2" width="100%">
<h4>
  <?php echo "Add Feedback"; ?>
</h4>
<?php
	$id = $_REQUEST['question'];
	$objQuestionTmp = Question::read($id);
	echo "<tr><td><b>".get_lang('Question')." : </b>";
	echo $objQuestionTmp->selectTitle();
	echo "</td></tr>";
	echo " <br><tr><td><b><br>".get_lang('Answer')." : </b></td></tr>";
	$objAnswerTmp=new Answer($id);
	$num = $objAnswerTmp->selectNbrAnswers();
	$objAnswerTmp->read();
	for($i=1;$i<=$num;$i++)
	{
	echo "<tr><td width='10%'> ";
	$ans =  $objAnswerTmp->answer[$i];
	$fck_attribute['Width'] = '600';
	$fck_attribute['Height'] = '200';
	$fck_attribute['ToolbarSet'] = 'Small'; //TODO: check where run? for customize buttons bar
		
	$form = new FormValidator('feedbackform','post',api_get_self()."?modifyQuestion=".$modifyQuestion."&newQuestion=".$newQuestion);
	$obj_registration_form = new HTML_QuickForm('frmRegistration', 'POST');
$renderer =& $obj_registration_form->defaultRenderer();
$renderer->setElementTemplate(
	'<tr>
	    <td align="left" style="" valign="top" width=30%>{label}
			<!-- BEGIN required --><span style="color: #ff0000">*</span><!-- END required -->
		</td>
	    <td align="left" width=70%>{element}
			<!-- BEGIN error --><br /><span style="color: #ff0000;font-size:10px">{error}</span><!-- END error -->
		</td>
	</tr>'
);
	$form->add_html_editor('Feedback',$i.'.'.$ans,false);
	$form->display();
	echo "</td>";
	}?>
	<form name="frm" action="#" method="post">
	 Click Ok to finish <input  type="submit" value="Ok" />
	</form>
