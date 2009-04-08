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
*	This script allows to manage answers. It is included from the script admin.php
*	@package dokeos.exercise
* 	@author Toon Keppens
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/


// ALLOWED_TO_INCLUDE is defined in admin.php
if(!defined('ALLOWED_TO_INCLUDE'))
{
	exit();
}
$modifyAnswers = $_GET['hotspotadmin'];

if(!is_object($objQuestion))
{
	$objQuestion = Question :: read($modifyAnswers);
}

$questionName=$objQuestion->selectTitle();
$answerType=$objQuestion->selectType();
$pictureName=$objQuestion->selectPicture();


$debug = 0; // debug variable to get where we are

$okPicture=empty($pictureName)?false:true;

// if we come from the warning box "this question is used in serveral exercises"
if($modifyIn)
{
    if($debug>0){echo '$modifyIn was set'."<br />\n";}
    // if the user has chosed to modify the question only in the current exercise
    if($modifyIn == 'thisExercise')
    {
        // duplicates the question
        $questionId=$objQuestion->duplicate();

        // deletes the old question
        $objQuestion->delete($exerciseId);

        // removes the old question ID from the question list of the Exercise object
        $objExercise->removeFromList($modifyAnswers);

        // adds the new question ID into the question list of the Exercise object
        $objExercise->addToList($questionId);

        // construction of the duplicated Question
        $objQuestion = Question :: read($questionId);

        // adds the exercise ID into the exercise list of the Question object
        $objQuestion->addToList($exerciseId);

        // copies answers from $modifyAnswers to $questionId
        $objAnswer->duplicate($questionId);

        // construction of the duplicated Answers

        $objAnswer=new Answer($questionId);
    }


    $color=unserialize($color);
    $reponse=unserialize($reponse);
    $comment=unserialize($comment);
    $weighting=unserialize($weighting);
    $hotspot_coordinates=unserialize($hotspot_coordinates);
    $hotspot_type=unserialize($hotspot_type);


    unset($buttonBack);
}

// the answer form has been submitted
if($submitAnswers || $buttonBack)
{
    if($debug>0){echo '$submitAnswers or $buttonBack was set'."<br />\n";}

    $questionWeighting=$nbrGoodAnswers=0;

    for($i=1;$i <= $nbrAnswers;$i++)
    {
        if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is HOT_SPOT'."<br />\n";}

        $reponse[$i]=trim($reponse[$i]);
        $comment[$i]=trim($comment[$i]);
        $weighting[$i]=intval($weighting[$i]);

        // checks if field is empty
        if(empty($reponse[$i]) && $reponse[$i] != '0')
        {
            $msgErr=get_lang('HotspotGiveAnswers');

            // clears answers already recorded into the Answer object
            $objAnswer->cancel();

            break;
        }

        if($weighting[$i] <= 0)
        {
        	$msgErr=get_lang('HotspotWeightingError');

        	// clears answers already recorded into the Answer object
            $objAnswer->cancel();

            break;
        }
        if($hotspot_coordinates[$i] == '0;0|0|0' || empty($hotspot_coordinates[$i]))
        {
        	$msgErr=get_lang('HotspotNotDrawn');

        	// clears answers already recorded into the Answer object
            $objAnswer->cancel();

            break;
        }

    }  // end for()


    if(empty($msgErr))
    {

    	for($i=1;$i <= $nbrAnswers;$i++)
        {
            if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is HOT_SPOT'."<br />\n";}

            $reponse[$i]=trim($reponse[$i]);
            $comment[$i]=addslashes(trim($comment[$i]));
            $weighting[$i]=intval($weighting[$i]);
			if($weighting[$i])
			{
				$questionWeighting+=$weighting[$i];
			}
			// creates answer
			$objAnswer->createAnswer($reponse[$i], '',$comment[$i],$weighting[$i],$i,$hotspot_coordinates[$i],$hotspot_type[$i]);
        }  // end for()
		// saves the answers into the data base
		$objAnswer->save();

        // sets the total weighting of the question
        $objQuestion->updateWeighting($questionWeighting);
        $objQuestion->save($exerciseId);

        $editQuestion=$questionId;

        unset($modifyAnswers);

        echo '<script type="text/javascript">window.location.href="admin.php"</script>';

    }
    if($debug>0){echo '$modifyIn was set - end'."<br />\n";}

}

if($modifyAnswers)
{


    if($debug>0){echo str_repeat('&nbsp;',0).'$modifyAnswers is set'."<br />\n";}

    // construction of the Answer object
    $objAnswer=new Answer($objQuestion -> id);

    api_session_register('objAnswer');

	if($debug>0){echo str_repeat('&nbsp;',2).'$answerType is HOT_SPOT'."<br />\n";}

	$TBL_ANSWERS = Database::get_course_table(TABLE_QUIZ_ANSWER);

	if(!$nbrAnswers)
    {

        $nbrAnswers=$objAnswer->selectNbrAnswers();

        $reponse=Array();
        $comment=Array();
        $weighting=Array();
        $hotspot_coordinates=Array();
        $hotspot_type=array();


        for($i=1;$i <= $nbrAnswers;$i++)
        {
            $reponse[$i]=$objAnswer->selectAnswer($i);
            $comment[$i]=$objAnswer->selectComment($i);
            $weighting[$i]=$objAnswer->selectWeighting($i);
            $hotspot_coordinates[$i]=$objAnswer->selectHotspotCoordinates($i);
            $hotspot_type[$i]=$objAnswer->selectHotspotType($i);
        }


    }

    $_SESSION['tmp_answers'] = array();
    $_SESSION['tmp_answers']['answer'] = $reponse;
    $_SESSION['tmp_answers']['comment'] = $comment;
    $_SESSION['tmp_answers']['weighting'] = $weighting;
    $_SESSION['tmp_answers']['hotspot_coordinates'] = $hotspot_coordinates;
    $_SESSION['tmp_answers']['hotspot_type'] = $hotspot_type;

    if($lessAnswers)
    {
    	// At least 1 answer
    	if ($nbrAnswers > 1) {

            $nbrAnswers--;

            // Remove the last answer
			$tmp = array_pop($_SESSION['tmp_answers']['answer']);
			$tmp = array_pop($_SESSION['tmp_answers']['comment']);
			$tmp = array_pop($_SESSION['tmp_answers']['weighting']);
			$tmp = array_pop($_SESSION['tmp_answers']['hotspot_coordinates']);
			$tmp = array_pop($_SESSION['tmp_answers']['hotspot_type']);
    	}
    	else
    	{
    		$msgErr=get_lang('MinHotspot');
    	}
    }

    if($moreAnswers)
    {
    	if ($nbrAnswers < 12)
    	{
            $nbrAnswers++;

            // Add a new answer
            $_SESSION['tmp_answers']['answer'][]='';
			$_SESSION['tmp_answers']['comment'][]='';
			$_SESSION['tmp_answers']['weighting'][]='1';
			$_SESSION['tmp_answers']['hotspot_coordinates'][]='0;0|0|0';
			$_SESSION['tmp_answers']['hotspot_type'][]='square';
    	}
    	else
    	{
    		$msgErr=get_lang('MaxHotspot');
    	}


    }

        if($debug>0){echo str_repeat('&nbsp;',2).'$usedInSeveralExercises is untrue'."<br />\n";}


        if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is HOT_SPOT'."<br />\n";}
        $hotspot_colors = array("", // $i starts from 1 on next loop (ugly fix)
        						"#4271B5",
								"#FE8E16",
								"#3B3B3B",
								"#BCD631",
								"#D63173",
								"#D7D7D7",
								"#90AFDD",
								"#AF8640",
								"#4F9242",
								"#F4EB24",
								"#ED2024",
								"#45C7F0",
								"#F7BDE2");
?>

<h3>
  <?php echo $langQuestion.": ".$questionName.' <img src="../img/info3.gif" title="'.strip_tags(get_lang('HotspotChoose')).'" alt="'.strip_tags(get_lang('HotspotChoose')).'" />'; ?>
</h3>
<?php
	if(!empty($msgErr))
	{
		Display::display_normal_message($msgErr); //main API
	}
	
?>

<form method="post" action="<?php echo api_get_self(); ?>?hotspotadmin=<?php echo $modifyAnswers; ?>" name="frm_exercise">
<table border="0" cellpadding="0" cellspacing="2" width="100%">

	<tr>
		<td colspan="2" valign="bottom">
			<button type="submit" class="minus" name="lessAnswers" value="<?php echo get_lang('LessHotspots'); ?>" ><?php echo get_lang('LessHotspots'); ?></button>
			<button type="submit" class="plus" name="moreAnswers" value="<?php echo get_lang('MoreHotspots'); ?>" /><?php echo get_lang('MoreHotspots'); ?></button>
			<button type="submit" class="cancel" name="cancelAnswers" value="<?php echo get_lang('Cancel'); ?>" onclick="javascript:if(!confirm('<?php echo addslashes(htmlentities(get_lang('ConfirmYourChoice'))); ?>')) return false;" ><?php echo get_lang('Cancel'); ?></button>
			<button type="submit" class="save" name="submitAnswers" value="<?php echo get_lang('Ok'); ?>" /><?php echo get_lang('SaveQuestion'); ?></button>
		</td>
	</tr>
	<tr>
		<td valign="top" style="border:1px solid #4271b5;border-top:none;border-bottom:none;border-right:none;" >			
				<input type="hidden" name="formSent" value="1" />
				<input type="hidden" name="nbrAnswers" value="<?php echo $nbrAnswers; ?>" />
				<table class="data_table">
					<!--
					<tr>
					  <td colspan="5"><?php echo get_lang('AnswerHotspot'); ?> :</td>
					</tr>
					-->
					<tr>
					  <th width="5">&nbsp;<?php /* echo get_lang('Hotspot'); */ ?></th>
					  <th ><?php echo get_lang('HotspotDescription'); ?>*</th>
					  <th ><?php echo get_lang('Comment'); ?></th>
					  <th><?php echo get_lang('QuestionWeighting'); ?>*</th>
					</tr>

					<?php
								for($i=1;$i <= $nbrAnswers;$i++)
								{
					?>

					<tr>
					  <td valign="top"><div style="height: 15px; width: 15px; background-color: <?php echo $hotspot_colors[$i]; ?>"> </div></td>
					  <td valign="top" align="left"><input type="text" name="reponse[<?php echo $i; ?>]" value="<?php echo htmlentities($reponse[$i]); ?>" size="45" /></td>
					  <td align="left"><textarea wrap="virtual" rows="1" cols="25" name="comment[<?php echo $i; ?>]" style="width: 100%"><?php echo stripslashes(htmlentities($comment[$i])); ?></textarea></td>
					  <td valign="top"><input type="text" name="weighting[<?php echo $i; ?>]" size="1" value="<?php echo (isset($weighting[$i]) ? $weighting[$i] : 10); ?>" />
					  <input type="hidden" name="hotspot_coordinates[<?php echo $i; ?>]" value="<?php echo (empty($hotspot_coordinates[$i]) ? '0;0|0|0' : $hotspot_coordinates[$i]); ?>" />
					  <input type="hidden" name="hotspot_type[<?php echo $i; ?>]" value="<?php echo (empty($hotspot_type[$i]) ? 'square' : $hotspot_type[$i]); ?>" /></td>
					</tr>

					<?php
					  			}
					?>

				</table>
		</td>	
	</tr>
	<tr>
		<td colspan="2" valign="top" style="border:1px solid #4271b5;border-top:none">
			<script type="text/javascript">
				<!--
				// Version check based upon the values entered above in "Globals"
				var hasReqestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);


				// Check to see if the version meets the requirements for playback
				if (hasReqestedVersion) {  // if we've detected an acceptable version
				    var oeTags = '<object type="application/x-shockwave-flash" data="../plugin/hotspot/hotspot_admin.swf?modifyAnswers=<?php echo $modifyAnswers ?>" width="720" height="650">'
								+ '<param name="movie" value="../plugin/hotspot/hotspot_admin.swf?modifyAnswers=<?php echo $modifyAnswers ?>" />'
								+ '<param name="test" value="OOoowww fo shooww" />'
								+ '</object>';
				    document.write(oeTags);   // embed the Flash Content SWF when all tests are passed
				} else {  // flash is too old or we can't detect the plugin
					var alternateContent = 'Error<br \/>'
						+ 'This content requires the Macromedia Flash Player.<br \/>'
						+ '<a href=http://www.macromedia.com/go/getflash/>Get Flash<\/a>';
					document.write(alternateContent);  // insert non-flash content
				}
				// -->
			</script>
		</td>
		
	</tr>
</table>
</form>



<?php

    if($debug>0){echo str_repeat('&nbsp;',0).'$modifyAnswers was set - end'."<br />\n";}
}
?>
