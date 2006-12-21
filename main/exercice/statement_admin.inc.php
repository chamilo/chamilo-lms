<?php // $Id: statement_admin.inc.php 10545 2006-12-21 15:09:31Z elixir_inter $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	STATEMENT ADMINISTRATION
*	This script allows to manage the statements of questions.
*	It is included from the script admin.php
*
*	@author Olivier Brouckaert
*	@package dokeos.exercise
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// ALLOWED_TO_INCLUDE is defined in admin.php
if(!defined('ALLOWED_TO_INCLUDE'))
{
	exit();
}

//debug var. Set to 0 if you don't want any debug display
$debug = 0;

$fck_attribute['Width'] = '80%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Small';

// the question form has been submitted
// this question form is the one below.
// In case the form has been submitted, at the end of the process part of this script, we "skip" the form
// display and pass to answer_admin.inc.php which displays a form for the answer itself
if($submitQuestion)
{
    if($debug>0){echo str_repeat('&nbsp;',2).'$submitQuestion is true'."<br />\n";}

    $questionName=trim(stripslashes($_POST['questionName']));
    $questionDescription=trim(stripslashes($_POST['questionDescription']));
    $_FILES['imageUpload']['name']=strtolower($_FILES['imageUpload']['name']);
    
    $hotspotErr = false;
    
    // no name given
    if(!$modifyQuestion && (empty($questionName) || ($answerType == HOT_SPOT && ($_FILES['imageUpload']['type'] != 'image/jpeg' && $_FILES['imageUpload']['type'] != 'image/pjpeg' && $_FILES['imageUpload']['type'] != 'image/jpg'))  || ($answerType == HOT_SPOT && empty($_FILES['imageUpload']['name']))))
    {
    	if(($_FILES['imageUpload']['type'] != 'image/jpeg' && $_FILES['imageUpload']['type'] != 'image/pjpeg' && $_FILES['imageUpload']['type'] != 'image/jpg') && !$modifyQuestion)
    	{
    		$msgErr = get_lang('langOnlyJPG');
    		$hotspotErr = true;
    	}
    	if(empty($_FILES['imageUpload']['name']) && !$modifyQuestion)
    	{
    		$msgErr=get_lang('NoImage');
    		$hotspotErr = true;
    	}
    	if(empty($questionName))
    	{
    		$msgErr=get_lang('GiveQuestion');
    	}
    }
    // checks if the question is used in several exercises
    elseif($exerciseId && !$modifyIn && $objQuestion->selectNbrExercises() > 1)
    {
        if($debug>0){echo str_repeat('&nbsp;',4).'$exerciseId is set and $modifyIn is unset and this question is in more than one exercise'."<br />\n";}
        $usedInSeveralExercises=1;

        // if a picture has been set
        if($_FILES['imageUpload']['size'])
        {
            // saves the picture into a temporary file
            $objQuestion->setTmpPicture($_FILES['imageUpload']['tmp_name'],$_FILES['imageUpload']['name']);
        }
    }
    else
    {
        if($debug>0){echo str_repeat('&nbsp;',4).'You have chosen to modify/add a question locally'."<br />\n";}
        // if the user has chosed to modify the question only in the current exercise
        if($modifyIn == 'thisExercise')
        {
        	// duplicates the question
        	$questionId=$objQuestion->duplicate();

            // deletes the old question
            $objQuestion->delete($exerciseId);

            // removes the old question ID from the question list of the Exercise object
            $objExercise->removeFromList($modifyQuestion);

            $nbrQuestions--;

            // construction of the duplicated Question
            $objQuestion=new Question();

            $objQuestion->read($questionId);

            // adds the exercise ID into the exercise list of the Question object
            $objQuestion->addToList($exerciseId);

            // construction of the Answer object
            $objAnswerTmp=new Answer($modifyQuestion);

            // copies answers from $modifyQuestion to $questionId
            $objAnswerTmp->duplicate($questionId);

            // destruction of the Answer object
            unset($objAnswerTmp);
        }

        $objQuestion->updateTitle($questionName);
        $objQuestion->updateDescription($questionDescription);
        
        $objQuestion->updateType($_REQUEST['answerType']);
        $objQuestion->save($exerciseId);

        // if a picture has been set or checkbox "delete" has been checked
        if($_FILES['imageUpload']['size'] || $deletePicture)
        {
            // we remove the picture
            $objQuestion->removePicture();

            // if we add a new picture
            if($_FILES['imageUpload']['size'])
            {
                // image is already saved in a temporary file
                if($modifyIn)
                {
                    $objQuestion->getTmpPicture();
                }
                // saves the picture coming from POST FILE
                else
                {
                    $objQuestion->uploadPicture($_FILES['imageUpload']['tmp_name'],$_FILES['imageUpload']['name']);
                	
                    if(!$objQuestion->resizePicture("any", 350))
                    {
                    	$msgErr = get_lang('langHotspotBadMetadata');
    					$hotspotErr = true;
    					
    					$objQuestion->removePicture();
                    }
                }
            }
            
            if($hotspotErr === false)
            {
            	$objQuestion->save($exerciseId);
            }
            else
            {
            	if($newQuestion)
            		$objQuestion->removeFromList($exerciseId);
            }
        }
        
        if($hotspotErr === false)
        {
        	$questionId=$objQuestion->selectId();

	        if($exerciseId)
	        {
	            // adds the question ID into the question list of the Exercise object
	            if($objExercise->addToList($questionId))
	            {
	                $objExercise->save();
	
	                $nbrQuestions++;
	            }
	        }

	        if($newQuestion)
	        {
	            // goes to answer administration
	            // -> answer_admin.inc.php
	            $modifyAnswers=$questionId;
	        }
	        else
	        {
	            // goes to exercise viewing
	            $editQuestion=$questionId;
	        }

        	// avoids displaying the following form in case we're editing the answer
        	unset($newQuestion,$modifyQuestion);
        }
    }
    if($debug>0){echo str_repeat('&nbsp;',2).'$submitQuestion is true - end'."<br />\n";}

}
else
{
    if($debug>0){echo str_repeat('&nbsp;',2).'$submitQuestion was unset'."<br />\n";}

    // if we don't come here after having cancelled the warning message "used in serveral exercises"
    if(!$buttonBack)
    {
        if($debug>0){echo str_repeat('&nbsp;',4).'$buttonBack was unset'."<br />\n";}
        $questionName=$objQuestion->selectTitle();
        $questionDescription=$objQuestion->selectDescription();
        $answerType= isset($_REQUEST['answerType']) ? $_REQUEST['answerType'] : $objQuestion->selectType();
        $pictureName=$objQuestion->selectPicture();
    }
	
    $okPicture=empty($pictureName)?false:true;
    if($debug>0){echo str_repeat('&nbsp;',2).'$submitQuestion was unset - end'."<br />\n";}
}
if(($newQuestion || $modifyQuestion) && !$usedInSeveralExercises)
{
    if($debug>0){echo str_repeat('&nbsp;',2).'$newQuestion or modifyQuestion was set but the question only exists in this exercise'."<br />\n";}

?>

<h3>
  <?php echo $questionName; ?>
</h3>

<?php
	if($okPicture)
	{
?>

<img src="../document/download.php?doc_url=%2Fimages%2F<?php echo $pictureName; ?>" border="0">
<?php
	}

	if(!empty($msgErr))
	{
	Display::display_normal_message($msgErr); //main API
	}
    //api_disp_html_area('questionDescription',$questionDescription,'250px');
$defaultType = isset($_REQUEST['answerType']) ? $_REQUEST['answerType'] : $answerType;
$user = array("questionDescription"=>$questionDescription,
                    "questionName"=>$questionName,
                    "answerType"=>$defaultType);


$form = new FormValidator('introduction_text','post',$_SERVER['PHP_SELF']."?modifyQuestion=".$modifyQuestion."&newQuestion=".$newQuestion);
//$renderer =&$form->defaultRenderer();
//$renderer->setElementTemplate('<div align ="left">{element}</div>');
	//$attrs = array("align"=>"right");

//$buttons[] = &$form->createElement('static','label1',get_lang('Question'));
//$buttons[] = &$form->createElement('text','questionName');
//$form->addGroup($buttons, null, null, '&nbsp;');

//$form->addelement('static','label1',get_lang('Question'));
$form->addelement('text','questionName',get_lang('Question'));
$form->addelement('hidden','myid',$_REQUEST['myid']);
$form->add_html_editor('questionDescription', get_lang('questionDescription'));
//$form->addElement('html_editor','questionDescription',get_lang('QuestionDescription'),false);
if($okPicture)
	{
	$form->addelement('checkbox','deletePicture',get_lang('DeletePicture'));
	}

if($modifyQuestion) {
	$obj_group_type[] = &HTML_QuickForm::createElement('radio', NULL, NULL, get_lang('UniqueSelect'),1);
	$obj_group_type[] = &HTML_QuickForm::createElement('radio', NULL, NULL, get_lang('MultipleSelect'),2);
	$obj_group_type[] = &HTML_QuickForm::createElement('radio', NULL, NULL, get_lang('Matching'),4);
	$obj_group_type[] = &HTML_QuickForm::createElement('radio', NULL, NULL, get_lang('FillBlanks'),3);
	$obj_group_type[] = &HTML_QuickForm::createElement('radio', NULL, NULL, get_lang('freeAnswer'),5);
	$obj_group_type[] = &HTML_QuickForm::createElement('radio', NULL, NULL, get_lang('Hotspot'),6);
	$form->addGroup($obj_group_type, 'answerType', get_lang('AnswerType').':','<br />');
}
else {
	$form->addElement('hidden','answerType',$_REQUEST['answerType']);
}

if($answerType == HOT_SPOT)
	$form->addElement('file','imageUpload');
$form->addElement('submit','submitQuestion',get_lang('Ok'));

 $form->setDefaults($user);

$form->display();

?>

<!--
 <td valign="top"><?php echo get_lang('AnswerType'); ?> :</td>
  <td><input class="checkbox" type="radio" name="answerType" value="1" <?php if($answerType <= 1) echo 'checked="checked"'; ?>> <?php echo get_lang('UniqueSelect'); ?><br />
	  <input class="checkbox" type="radio" name="answerType" value="2" <?php if($answerType == 2) echo 'checked="checked"'; ?>> <?php echo get_lang('MultipleSelect'); ?><br />
	  <input class="checkbox" type="radio" name="answerType" value="4" <?php if($answerType == 4) echo 'checked="checked"'; ?>> <?php echo get_lang('Matching'); ?><br />
	  <input class="checkbox" type="radio" name="answerType" value="3" <?php if($answerType == 3) echo 'checked="checked"'; ?>> <?php echo get_lang('FillBlanks'); ?><br />
    <input class="checkbox" type="radio" name="answerType" value="5" <?php if($answerType == 5) echo 'checked="checked"'; ?>> <?php echo get_lang('freeAnswer'); ?>
  	<input class="checkbox" type="radio" name="answerType" value="6" <?php if($answerType == 6) echo 'checked="checked"'; ?>> <?php echo get_lang('Hotspot'); ?>
  </td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td>
	<input type="hidden" name="myid" value="<?php echo $_REQUEST['myid'];?>">
	<input type="submit" name="submitQuestion" value="<?php echo get_lang('Ok'); ?>">
	<!-- &nbsp;&nbsp;<input type="submit" name="cancelQuestion" value="<?php echo get_lang('Cancel'); ?>" onclick="javascript:if(!confirm('<?php echo addslashes(htmlentities(get_lang('ConfirmYourChoice'))); ?>')) return false;">
<!--  </td>
</tr>
</table>
</form>-->

<?php
}
?>