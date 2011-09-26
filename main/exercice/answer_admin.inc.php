<?php
/* For licensing terms, see /license.txt */
// YW: 20110209: Script depredated? 
/**
*	This script allows to manage answers. It is included from the script admin.php
*	@package chamilo.exercise
* 	@author Olivier Brouckaert
* 	@version $Id: answer_admin.inc.php 21361 2009-06-11 04:08:58Z ivantcholakov $
*/
/**
 * Code
 */

// ALLOWED_TO_INCLUDE is defined in admin.php
if(!defined('ALLOWED_TO_INCLUDE'))
{
	exit();
}
if(!is_object($objQuestion))
{
	$objQuestion = Question :: read($_GET['modifyAnswers']);
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
        $objQuestion = Question::read($questionId);

        // adds the exercise ID into the exercise list of the Question object
        $objQuestion->addToList($exerciseId);

        // copies answers from $modifyAnswers to $questionId
        $objAnswer->duplicate($questionId);

        // construction of the duplicated Answers

        $objAnswer=new Answer($questionId);
    }

    if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER || $answerType == MULTIPLE_ANSWER_COMBINATION)
    {
        $correct=unserialize($correct);
        $reponse=unserialize($reponse);
        $comment=unserialize($comment);
        $weighting=unserialize($weighting);
    }
    //matching
    elseif($answerType == MATCHING)
    {
        $option=unserialize($option);
        $match=unserialize($match);
        $sel=unserialize($sel);
        $weighting=unserialize($weighting);
    }
    //free answer
    elseif($answerType == FREE_ANSWER )
    {
        $reponse=unserialize($reponse);
        $comment=unserialize($comment);
        $free_comment=$comment;
        $weighting=unserialize($weighting);

	}
	elseif ($answerType == HOT_SPOT || $answerType == HOT_SPOT_ORDER || $answerType == HOT_SPOT_DELINEATION)
    {
        $color=unserialize($color);
        $reponse=unserialize($reponse);
        $comment=unserialize($comment);
        $weighting=unserialize($weighting);
        $hotspot_coordinates=unserialize($hotspot_coordinates);
        $hotspot_type=unserialize($hotspot_type);


    }
    //fill in blanks
  else
    {
        $reponse=unserialize($reponse);
        $comment=unserialize($comment);
        $blanks=unserialize($blanks);
        $weighting=unserialize($weighting);
    }

    unset($buttonBack);
}

// the answer form has been submitted
if($submitAnswers || $buttonBack)
{
    if($debug>0){echo '$submitAnswers or $buttonBack was set'."<br />\n";}
    if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER || $answerType == MULTIPLE_ANSWER_COMBINATION) {
      if($debug>0){echo '&nbsp;&nbsp;$answerType is UNIQUE_ANSWER or MULTIPLE_ANSWER'."<br />\n";}
        $questionWeighting=$nbrGoodAnswers=0;

        for($i=1;$i <= $nbrAnswers;$i++) {
            $reponse[$i]=trim($reponse[$i]);
            $comment[$i]=trim($comment[$i]);
            $weighting[$i]=intval($weighting[$i]);

            if($answerType == UNIQUE_ANSWER)
            {
            if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is UNIQUE_ANSWER'."<br />\n";}
                $goodAnswer=($correct == $i)?1:0;
            }
            else
            {
                $goodAnswer=$correct[$i];
            }

            if($goodAnswer)
            {
                $nbrGoodAnswers++;

                // a good answer can't have a negative weighting
                $weighting[$i]=abs($weighting[$i]);

                // calculates the sum of answer weighting only if it is different from 0 and the answer is good
                if($weighting[$i])
                {
                    $questionWeighting+=$weighting[$i];
                }
            } elseif($answerType == MULTIPLE_ANSWER) {
            if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is MULTIPLE_ANSWER'."<br />\n";}
                // a bad answer can't have a positive weighting
                $weighting[$i]=0-abs($weighting[$i]);
            }
            // checks if field is empty
            if(empty($reponse[$i]) && $reponse[$i] != '0')
            {
                $msgErr=get_lang('GiveAnswers');

                // clears answers already recorded into the Answer object
                $objAnswer->cancel();

                break;
            } else {
                // adds the answer into the object
                $objAnswer->createAnswer($reponse[$i],$goodAnswer,$comment[$i],$weighting[$i],$i);
            	//added
				//if($_REQUEST['myid']==1)
                    $mainurl="admin.php";
				//	else
                  //  $mainurl="question_pool.php";
					?>
					<script>
					window.location.href='<?php echo $mainurl;?>';
					</script>
					<?php
			}
        }  // end for()

        if(empty($msgErr))
        {
            if(!$nbrGoodAnswers)
            {
                $msgErr=($answerType == UNIQUE_ANSWER)?get_lang('ChooseGoodAnswer'):get_lang('ChooseGoodAnswers');

                // clears answers already recorded into the Answer object
                $objAnswer->cancel();
            }
            // checks if the question is used in several exercises
            elseif($exerciseId && !$modifyIn && $objQuestion->selectNbrExercises() > 1)
            {
                $usedInSeveralExercises=1;
            }
            else
            {
                // saves the answers into the data base
                $objAnswer->save();

                // sets the total weighting of the question
                $objQuestion->updateWeighting($questionWeighting);
                $objQuestion->save($exerciseId);

                $editQuestion=$questionId;

                unset($modifyAnswers);
            }
        }
    }
    elseif($answerType == FILL_IN_BLANKS)
    {
        if($debug>0){echo str_repeat('&nbsp;',2).'$answerType is FILL_IN_BLANKS'."<br />\n";}
        $reponse=trim($reponse);

        if(!$buttonBack)
        {
            if($debug>0){echo str_repeat('&nbsp;',4).'$buttonBack is not set'."<br />\n";}
            if($setWeighting)
            {
                $blanks=unserialize($blanks);

                // checks if the question is used in several exercises
                if($exerciseId && !$modifyIn && $objQuestion->selectNbrExercises() > 1)
                {
                    $usedInSeveralExercises=1;
                }
                else
                {
                    // separates text and weightings by '::'
                    $reponse.='::';

                    $questionWeighting=0;

                    foreach($weighting as $val)
                    {
                        // a blank can't have a negative weighting
                        $val=abs($val);

                        $questionWeighting+=$val;

                        // adds blank weighting at the end of the text
                        $reponse.=$val.',';
                    }

                    $reponse=api_substr($reponse,0,-1);

                    $objAnswer->createAnswer($reponse,0,'',0,'');
                    $objAnswer->save();
					//added
					//if($_REQUEST['myid']==1)
                    $mainurl="admin.php";
				//	else
                  //  $mainurl="question_pool.php";
					?>
					<script>
					window.location.href='<?php echo $mainurl;?>';
					</script>
					<?php


                    // sets the total weighting of the question
                    $objQuestion->updateWeighting($questionWeighting);
                    $objQuestion->save($exerciseId);

                    $editQuestion=$questionId;

                    unset($modifyAnswers);
                }
            }
            // if no text has been typed or the text contains no blank
            elseif(empty($reponse))
            {
                $msgErr=get_lang('GiveText');
            }
            elseif(!api_ereg('\[.+\]',$reponse))
            {
                $msgErr=get_lang('DefineBlanks');
            }
            else
            {
                // now we're going to give a weighting to each blank
                $setWeighting=1;

                unset($submitAnswers);

                // removes character '::' possibly inserted by the user in the text
                $reponse=str_replace('::','',$reponse);

                // we save the answer because it will be modified
                //$temp=$reponse;
                $temp = text_filter($reponse);

                /* // Deprecated code.
                // 1. find everything between the [tex] and [/tex] tags
                $startlocations=api_strpos($temp,'[tex]');
                $endlocations=api_strpos($temp,'[/tex]');

                if($startlocations !== false && $endlocations !== false)
                {
                    $texstring=api_substr($temp,$startlocations,$endlocations-$startlocations+6);

                    // 2. replace this by {texcode}
                    $temp=str_replace($texstring,"{texcode}",$temp);
                }
                */

                // blanks will be put into an array
                $blanks=Array();

                $i=1;

                // the loop will stop at the end of the text
                while(1)
                {
                    // quits the loop if there are no more blanks
                    if(($pos = api_strpos($temp,'[')) === false)
                    {
                        break;
                    }

                    // removes characters till '['
                    $temp=api_substr($temp,$pos+1);

                    // quits the loop if there are no more blanks
                    if(($pos = api_strpos($temp,']')) === false)
                    {
                        break;
                    }

                    // stores the found blank into the array
                    $blanks[$i++]=api_substr($temp,0,$pos);

                    // removes the character ']'
                    $temp=api_substr($temp,$pos+1);
                }
            }
        }
        else
        {
            unset($setWeighting);
        }
    }
    elseif($answerType == FREE_ANSWER)
    {
        if($debug>0){echo str_repeat('&nbsp;',2).'$answerType is FREE_ANSWER'."<br />\n";}
        if ( empty ( $free_comment ) ) {
            $free_comment = $_POST['comment'];
        }
        if ( empty ( $weighting ) ) {
            $weighting = $_POST['weighting'];
			$weightingtemp = $_POST['weighting'];

		}

        if(!$buttonBack)
        {
            if($debug>0){echo str_repeat('&nbsp;',4).'$buttonBack is not set'."<br />\n";}
            if($setWeighting)
            {
                if($debug>0){echo str_repeat('&nbsp;',6).'$setWeighting is set'."<br />\n";}
                // checks if the question is used in several exercises
                if($exerciseId && !$modifyIn && $objQuestion->selectNbrExercises() > 1)
                {
                    $usedInSeveralExercises=1;
                }
                else
                {

                    $objAnswer->createAnswer('',0,$free_comment,$weighting,'');
                    $objAnswer->save();
					// sets the total weighting of the question
                    $objQuestion->updateWeighting($weighting);
                    $objQuestion->save($exerciseId);

                    $editQuestion=$questionId;

                    unset($modifyAnswers);//added
					//if($_REQUEST['myid']==1)
                   $mainurl="admin.php";
				//	else
                  //  $mainurl="question_pool.php";
					?>
					<script>
					window.location.href='<?php echo $mainurl;?>';
					</script>
					<?php
                }
            }
            // if no text has been typed or the text contains no blank
            elseif(empty($free_comment))
            {
                if($debug>0){echo str_repeat('&nbsp;',6).'$free_comment is empty'."<br />\n";}
                $msgErr=get_lang('GiveText');
            }
            /*elseif(!ereg('\[.+\]',$reponse))
            {
                $msgErr=get_lang('DefineBlanks');
            }*/
            else
            {
                if($debug>0){echo str_repeat('&nbsp;',6).'$setWeighting is not set and $free_comment is not empty'."<br />\n";}

                // now we're going to give a weighting to each blank
                $setWeighting=1;

                unset($submitAnswers);
            }
        }
        else
        {
            unset($setWeighting);
        }
    }
    elseif($answerType == MATCHING)
    {
        if($debug>0){echo str_repeat('&nbsp;',2).'$answerType is MATCHING'."<br />\n";}
        for($i=1;$i <= $nbrOptions;$i++)
        {
            $option[$i]=trim($option[$i]);

            // checks if field is empty
            if(empty($option[$i]) && $option[$i] != '0')
            {
                $msgErr=get_lang('FillLists');

                // clears options already recorded into the Answer object
                $objAnswer->cancel();

                break;
            }
            else
            {
                // adds the option into the object
                $objAnswer->createAnswer($option[$i],0,'',0,$i);
            }
        }

        $questionWeighting=0;

        if(empty($msgErr))
        {
            for($j=1;$j <= $nbrMatches;$i++,$j++)
            {
                $match[$i]=trim($match[$i]);
                $weighting[$i]=abs(intval($weighting[$i]));

                $questionWeighting+=$weighting[$i];

                // checks if field is empty
                if(empty($match[$i]) && $match[$i] != '0')
                {
                    $msgErr=get_lang('FillLists');

                    // clears matches already recorded into the Answer object
                    $objAnswer->cancel();

                    break;
                }
                // check if correct number
                else
                {
                    // adds the answer into the object
                    $objAnswer->createAnswer($match[$i],$sel[$i],'',$weighting[$i],$i);
					//added
					//if($_REQUEST['myid']==1)
                    $mainurl="admin.php";
					//else
                    //$mainurl="question_pool.php";
					?>
					<script>
					window.location.href='<?php echo $mainurl;?>';
					</script>
					<?php
                }
            }
        }

        if(empty($msgErr))
        {
            // checks if the question is used in several exercises
            if($exerciseId && !$modifyIn && $objQuestion->selectNbrExercises() > 1)
            {
                $usedInSeveralExercises=1;
            }
            else
            {
                // all answers have been recorded, so we save them into the data base
                $objAnswer->save();

                // sets the total weighting of the question
                $objQuestion->updateWeighting($questionWeighting);
                $objQuestion->save($exerciseId);

                $editQuestion=$questionId;

                unset($modifyAnswers);
            }
        }
    }
    elseif($answerType == HOT_SPOT || $answerType == HOT_SPOT_ORDER)
    {
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
            if($exerciseId && !$modifyIn && $objQuestion->selectNbrExercises() > 1)
            {
                $usedInSeveralExercises=1;
            }
            else
            {
            	for($i=1;$i <= $nbrAnswers;$i++)
		        {
		            if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is HOT_SPOT'."<br />\n";}

		            $reponse[$i]=trim($reponse[$i]);
		            $comment[$i]=trim($comment[$i]);
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
            }
        }
    }
    if($debug>0){echo '$modifyIn was set - end'."<br />\n";}

}

if($modifyAnswers)
{


    if($debug>0){echo str_repeat('&nbsp;',0).'$modifyAnswers is set'."<br />\n";}

    // construction of the Answer object
    $objAnswer=new Answer($questionId);


    api_session_register('objAnswer');

    if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER)
    {
       if($debug>0){echo str_repeat('&nbsp;',2).'$answerType is UNIQUE_ANSWER or MULTIPLE_ANSWER'."<br />\n";}
        if(!$nbrAnswers)
        {
            $nbrAnswers=$objAnswer->selectNbrAnswers();

            $reponse=Array();
            $comment=Array();
            $weighting=Array();

            // initializing
            if($answerType == MULTIPLE_ANSWER)
            {
                $correct=Array();
            }
            else
            {
                $correct=0;
            }

            for($i=1;$i <= $nbrAnswers;$i++)
            {
                $reponse[$i]=$objAnswer->selectAnswer($i);
                $comment[$i]=$objAnswer->selectComment($i);
                $weighting[$i]=$objAnswer->selectWeighting($i);

                if($answerType == MULTIPLE_ANSWER)
                {
                    $correct[$i]=$objAnswer->isCorrect($i);
                }
                elseif($objAnswer->isCorrect($i))
                {
                    $correct=$i;
                }
            }
        }

        if($lessAnswers)
        {
            $nbrAnswers--;
        }

        if($moreAnswers)
        {
            $nbrAnswers++;
        }

        // minimum 2 answers
        if($nbrAnswers < 2)
        {
            $nbrAnswers=2;
        }
    }
    elseif($answerType == FILL_IN_BLANKS)
    {
       if($debug>0){echo str_repeat('&nbsp;',2).'$answerType is FILL_IN_BLANKS'."<br />\n";}
        if(!$submitAnswers && !$buttonBack)
        {
            if(!$setWeighting)
            {
                $reponse=$objAnswer->selectAnswer(1);

                list($reponse,$weighting)=explode('::',$reponse);

                $weighting=explode(',',$weighting);

                $temp=Array();

                // keys of the array go from 1 to N and not from 0 to N-1
                for($i=0;$i < sizeof($weighting);$i++)
                {
                    $temp[$i+1]=$weighting[$i];
                }

                $weighting=$temp;
            }
            elseif(!$modifyIn)
            {
                $weighting=unserialize($weighting);
            }
        }
    }
    elseif($answerType == FREE_ANSWER)
    {
        if($debug>0){echo str_repeat('&nbsp;',2).'$answerType is FREE_ANSWER'."<br />\n";}
        if(!$submitAnswers && !$buttonBack)
        {
            if($debug>0){echo str_repeat('&nbsp;',4).'$submitAnswers && $buttonsBack are unset'."<br />\n";}
            if(!$setWeighting)
            {
                if($debug>0){echo str_repeat('&nbsp;',6).'$setWeighting is unset'."<br />\n";}

                //YW: not quite  sure about whether the comment has already been recovered,
                // but as we have passed into the submitAnswers loop, this should be in the
                // objAnswer object.
                $free_comment = $objAnswer->selectComment(1);
				$weighting=$objAnswer->selectWeighting(1); //added
            }
            elseif(!$modifyIn)
            {
                if($debug>0){echo str_repeat('&nbsp;',6).'$setWeighting is set and $modifyIn is unset'."<br />\n";}
                $weighting=unserialize($weighting);
            }
        }
    }
    elseif($answerType == MATCHING)
    {
        if($debug>0){echo str_repeat('&nbsp;',2).'$answerType is MATCHING'."<br />\n";}
        if(!$nbrOptions || !$nbrMatches)
        {
            $option=Array();
            $match=Array();
            $sel=Array();

            $nbrOptions=$nbrMatches=0;

            // fills arrays with data from de data base
            for($i=1;$i <= $objAnswer->selectNbrAnswers();$i++)
            {
                // it is a match
                if($objAnswer->isCorrect($i))
                {
                    $match[$i]=$objAnswer->selectAnswer($i);
                    $sel[$i]=$objAnswer->isCorrect($i);
                    $weighting[$i]=$objAnswer->selectWeighting($i);
                    $nbrMatches++;
                }
                // it is an option
                else
                {
                    $option[$i]=$objAnswer->selectAnswer($i);
                    $nbrOptions++;
                }
            }
        }

        if($lessOptions)
        {
            // keeps the correct sequence of array keys when removing an option from the list
            for($i=$nbrOptions+1,$j=1;$nbrOptions > 2 && $j <= $nbrMatches;$i++,$j++)
            {
                $match[$i-1]=$match[$i];
                $sel[$i-1]=$sel[$i];
                $weighting[$i-1]=$weighting[$i];
            }

            unset($match[$i-1]);
            unset($sel[$i-1]);

            $nbrOptions--;
        }

        if($moreOptions)
        {
            // keeps the correct sequence of array keys when adding an option into the list
            for($i=$nbrMatches+$nbrOptions;$i > $nbrOptions;$i--)
            {
                $match[$i+1]=$match[$i];
                $sel[$i+1]=$sel[$i];
                $weighting[$i+1]=$weighting[$i];
            }

            unset($match[$i+1]);
            unset($sel[$i+1]);

            $nbrOptions++;
        }

        if($lessMatches)
        {
            $nbrMatches--;
        }

        if($moreMatches)
        {
            $nbrMatches++;
        }

        // minimum 2 options
        if($nbrOptions < 2)
        {
            $nbrOptions=2;
        }

        // minimum 2 matches
        if($nbrMatches < 2)
        {
            $nbrMatches=2;
        }

    }
    elseif ($answerType == HOT_SPOT || $answerType == HOT_SPOT_ORDER)
    {
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

    }

    if(!$usedInSeveralExercises)
    {
        if($debug>0){echo str_repeat('&nbsp;',2).'$usedInSeveralExercises is untrue'."<br />\n";}

        if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER || $answerType == MULTIPLE_ANSWER_COMBINATION)
        {
            if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is UNIQUE_ANSWER or MULTIPLE_ANSWER'."<br />\n";}

?>
<h3>
  <?php echo $questionName; ?>
</h3>
<?php
		/*if ($exerciseId==0){
	?>
		<form method="post" action="<?php echo api_get_self(); ?>?modifyAnswers=<?php  echo $modifyAnswers; ?>">
		<?php }
		else
		{
		?>
		<form method="post" action="<?php echo api_get_self(); ?>?exerciseId=<?php  echo $exerciseId; ?>">
		<?php
		}*/


?>
<form method="post" action="<?php echo api_get_self(); ?>?modifyAnswers=<?php  echo $modifyAnswers; ?>">


<input type="hidden" name="formSent" value="1">
<input type="hidden" name="nbrAnswers" value="<?php echo $nbrAnswers; ?>">
<input type="hidden" name="myid" value="<?php echo $_REQUEST['myid']; ?>">

<table width="650" border="0" cellpadding="5">

<?php
			if($okPicture)
			{
?>

<tr>
  <td colspan="5" align="center"><img src="../document/download.php?doc_url=%2Fimages%2F<?php echo $pictureName; ?>" border="0"></td>
</tr>

<?php
			}

			if(!empty($msgErr))
			{
?>

<tr>
  <td colspan="5">

<?php
	Display::display_normal_message($msgErr); //main API
?>

  </td>
</tr>

<?php
			}
?>

<tr>
  <td colspan="5"><?php echo get_lang('Answers'); ?> :</td>
</tr>
<tr bgcolor="#E6E6E6">
  <td>N&#176;</td>
  <td><?php echo get_lang('True'); ?></td>
  <td><?php echo get_lang('Answer'); ?></td>
  <td><?php echo get_lang('Comment'); ?></td>
  <td><?php echo get_lang('QuestionWeighting'); ?></td>
</tr>

<?php
			for($i=1;$i <= $nbrAnswers;$i++)
			{
?>

<tr>
  <td valign="top"><?php echo $i; ?></td>

<?php
				if($answerType == UNIQUE_ANSWER)
				{
?>

  <td valign="top"><input class="checkbox" type="radio" value="<?php echo $i; ?>" name="correct" <?php if($correct == $i) echo 'checked="checked"'; ?>></td>

<?php
				}
				else
				{
?>

  <td valign="top"><input class="checkbox" type="checkbox" value="1" name="correct[<?php echo $i; ?>]" <?php if($correct[$i]) echo 'checked="checked"'; ?>></td>

<?php
				}
?>

  <td align="left"><textarea wrap="virtual" rows="7" cols="25" name="reponse[<?php echo $i; ?>]"><?php echo api_htmlentities($reponse[$i],ENT_QUOTES,$charset); ?></textarea></td>
  <td align="left"><textarea wrap="virtual" rows="7" cols="25" name="comment[<?php echo $i; ?>]"><?php echo api_htmlentities($comment[$i],ENT_QUOTES,$charset); ?></textarea></td>

    <td valign="top"><input type="text" name="weighting[<?php echo $i; ?>]" size="5" value="<?php echo isset($weighting[$i])?$weighting[$i]:0; ?>"></td>
</tr>

<?php
  			}
?>

<tr>
  <td colspan="5">
	<input type="submit" name="submitAnswers" value="<?php echo get_lang('Ok'); ?>">
	&nbsp;&nbsp;<input type="submit" name="lessAnswers" value="<?php echo get_lang('LessAnswers'); ?>">
	&nbsp;&nbsp;<input type="submit" name="moreAnswers" value="<?php echo get_lang('MoreAnswers'); ?>">
	<!-- &nbsp;&nbsp;<input type="submit" name="cancelAnswers" value="<?php echo get_lang('Cancel'); ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)); ?>')) return false;"> //-->
  </td>
</tr>
</table>
</form>

<?php
        }
        elseif($answerType == FILL_IN_BLANKS)
        {
 if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is FILL_IN_BLANKS'."<br />\n";}

?>

<h3>
  <?php echo $questionName; ?>
</h3>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?modifyAnswers=<?php echo $modifyAnswers; ?>">
<input type="hidden" name="formSent" value="1">
<input type="hidden" name="setWeighting" value="<?php echo $setWeighting; ?>">
<input type="hidden" name="myid" value="<?php echo $_REQUEST['myid']; ?>">

<?php
            if(!$setWeighting)
            {
?>

<input type="hidden" name="weighting" value="<?php echo $submitAnswers?api_htmlentities($weighting,ENT_QUOTES,$charset):api_htmlentities(serialize($weighting),ENT_QUOTES,$charset); ?>">

<table border="0" cellpadding="5" width="500">

<?php
                if($okPicture)
               {
?>

<tr>
  <td align="center"><img src="../document/download.php?doc_url=%2Fimages%2F<?php echo $pictureName; ?>" border="0"></td>
</tr>

<?php
                }

                if(!empty($msgErr))
                {
?>

<tr>
  <td colspan="2">

<?php
                    Display::display_normal_message($msgErr); //main API
?>

  </td>
</tr>

<?php
                }
?>

<tr>
  <td><?php echo get_lang('TypeTextBelow').', '.get_lang('And').' '.get_lang('UseTagForBlank'); ?> :</td>
</tr>
<tr>
  <td><textarea wrap="virtual" name="reponse" cols="65" rows="6"><?php if(!$submitAnswers && empty($reponse)) echo get_lang('DefaultTextInBlanks'); else echo api_htmlentities($reponse,ENT_QUOTES,$charset); ?></textarea></td>
</tr>
<tr>
  <td colspan="5">
	<!-- <input type="submit" name="cancelAnswers" value="<?php echo get_lang('Cancel'); ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)); ?>')) return false;">
	&nbsp;&nbsp; //--> <input type="submit" name="submitAnswers" value="<?php echo get_lang('Ok'); ?>">
  </td>
</tr>
</table>

<?php
}
 else
{
?>
<input type="hidden" name="blanks" value="<?php echo api_htmlentities(serialize($blanks),ENT_QUOTES,$charset); ?>">
<input type="hidden" name="reponse" value="<?php echo api_htmlentities($reponse,ENT_QUOTES,$charset); ?>">
<table border="0" cellpadding="5" width="500">
<?php
                if(!empty($msgErr))
                {
?>

<tr>
  <td colspan="2">

<?php
                    Display::display_normal_message($msgErr); //main API
?>

  </td>
</tr>

<?php
                }
?>

<tr>
  <td colspan="2"><?php echo get_lang('WeightingForEachBlank'); ?> :</td>
</tr>
<tr>
  <td colspan="2">&nbsp;</td>
</tr>

<?php
                foreach($blanks as $i=>$blank)
                {
?>

<tr>
  <td width="50%"><?php echo $blank; ?> :</td>
  <td width="50%"><input type="text" name="weighting[<?php echo $i; ?>]" size="5" value="<?php echo intval($weighting[$i]); ?>"></td>
</tr>

<?php
                }
?>

<tr>
  <td colspan="2">&nbsp;</td>
</tr>
<tr>
  <td colspan="2">
	<input type="submit" name="buttonBack" value="&lt; <?php echo get_lang('Back'); ?>">
	&nbsp;&nbsp;<input type="submit" name="submitAnswers" value="<?php echo '  '.get_lang('Ok').'  '; ?>">
	<!-- &nbsp;&nbsp;<input type="submit" name="cancelAnswers" value="<?php echo get_lang('Cancel'); ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)); ?>')) return false;"> //-->
  </td>
</tr>
</table>

<?php
            }
?>

</form>

<?php
        }
        elseif($answerType == FREE_ANSWER) //edited by Priya Saini
        {
            if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is FREE_ANSWER'."<br />\n";}
			?>
			<h3>
			<?php echo $questionName;?></h3><?php
			$sql = "select description from ".$TBL_QUESTIONS." WHERE id = '".Database::escape_string($questionId)."'";
			$res = Database::query($sql,_FILE_,_LINE_);
			?>
			&nbsp; &nbsp; &nbsp;
			<?php
			echo $desc = Database::result($res,0,'description');
			 ?>

			<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?modifyAnswers=<?php echo $modifyAnswers; ?>">
			<input type="hidden" name="formSent" value="1">
			<input type="hidden" name="setWeighting" value="1">
			<input type="hidden" name="myid" value="<?php  echo $_REQUEST['myid'];?>">
 			<table border="0" cellpadding="5" width="500">
			<?php
                if($okPicture)
                { echo "Ok picture";
			?>

				<tr>
				 <td align="center"><img src="../document/download.php?doc_url=%2Fimages%2F<?php echo $pictureName; ?>" border="0"></td>
				</tr>
			<?php
                }

                if(!empty($msgErr))
                {
			?>
				<tr>
				<td colspan="2">
			<?php
                    Display::display_normal_message($msgErr); //main API
			?>

  				</td>
				</tr>
			<?php
                }
 			if(!$submitAnswers && empty($free_comment))
				echo '';
			else
				echo api_htmlentities($free_comment,ENT_QUOTES,$charset); ?>
   				<tr><td width="22%"><?php echo get_lang('QuestionWeighting'); ?></td>
				<td width="78%"><input type="text" size="4" name="weighting" value="<?php if(!$submitAnswers && !isset($weighting)) echo '0'; else echo $weighting; ?>"></td>
				</tr>
				<tr>
			   <td colspan="5">
					<input type="submit" name="submitAnswers" value="<?php echo get_lang('Ok'); ?>">
  				</td>
				</tr>
			</table>
<?php
	   }
      //end of FREE_ANSWER type*/
        elseif($answerType == MATCHING)
        {
?>

<h3>
  <?php echo $questionName; ?>
</h3>

<form method="post" action="<?php echo api_get_self(); ?>?modifyAnswers=<?php echo $modifyAnswers; ?>">
<input type="hidden" name="formSent" value="1">
<input type="hidden" name="nbrOptions" value="<?php echo $nbrOptions; ?>">
<input type="hidden" name="nbrMatches" value="<?php echo $nbrMatches; ?>">
<input type="hidden" name="myid" value="<?php echo $_REQUEST['myid'];?>">

<table border="0" cellpadding="5">

<?php
            if($okPicture)
            {
?>

<tr>
  <td colspan="4" align="center"><img src="../document/download.php?doc_url=%2Fimages%2F<?php echo $pictureName; ?>" border="0"></td>
</tr>

<?php
            }

            if(!empty($msgErr))
            {
?>

<tr>
  <td colspan="4">

<?php
                Display::display_normal_message($msgErr); //main API
?>

  </td>
</tr>

<?php
            }

            $listeOptions=Array();

            // creates an array with the option letters
            for($i=1,$j='A';$i <= $nbrOptions;$i++,$j++)
            {
                $listeOptions[$i]=$j;
            }
?>

<tr>
  <td colspan="3"><?php echo get_lang('MakeCorrespond'); ?> :</td>
  <td><?php echo get_lang('QuestionWeighting'); ?> :</td>
</tr>

<?php
            for($j=1;$j <= $nbrMatches;$i++,$j++)
            {
?>

<tr>
  <td><?php echo $j; ?></td>
  <td><input type="text" name="match[<?php echo $i; ?>]" size="58" value="<?php if(!$formSent && !isset($match[$i])) echo ${"langDefaultMakeCorrespond$j"}; else echo api_htmlentities($match[$i],ENT_QUOTES,$charset); ?>"></td>
  <td align="center"><select name="sel[<?php echo $i; ?>]">

<?php
                foreach($listeOptions as $key=>$val)
                {
?>

	<option value="<?php echo $key; ?>" <?php if((!$submitAnswers && !isset($sel[$i]) && $j == 2 && $val == 'B') || $sel[$i] == $key) echo 'selected="selected"'; ?>><?php echo $val; ?></option>

<?php
                } // end foreach()
?>

  </select></td>
  <td align="center"><input type="text" size="8" name="weighting[<?php echo $i; ?>]" value="<?php if(!$submitAnswers && !isset($weighting[$i])) echo '5'; else echo $weighting[$i]; ?>"></td>
</tr>

<?php
            } // end for()
?>

<tr>
  <td colspan="4">
	<button type="submit" class="minus" name="lessMatches" value="<?php echo get_lang('LessElements'); ?>"><?php echo get_lang('LessElements'); ?></button>
	&nbsp;&nbsp;<button class="plus" type="submit" name="moreMatches" value="<?php echo get_lang('MoreElements'); ?>"><?php echo get_lang('MoreElements'); ?></button>
  </td>
</tr>
<tr>
  <td colspan="4"><?php echo get_lang('DefineOptions'); ?> :</td>
</tr>

<?php
            foreach($listeOptions as $key=>$val)
            {
?>

<tr>
  <td><?php echo $val; ?></td>
  <td colspan="3"><input type="text" name="option[<?php echo $key; ?>]" size="80" value="<?php if(!$formSent && !isset($option[$key])) echo get_lang("DefaultMatchingOpt$val"); else echo api_htmlentities($option[$key],ENT_QUOTES,$charset); ?>"></td>
</tr>

<?php
            } // end foreach()
?>

<tr>
  <td colspan="4">
	<button type="submit" class="minus" name="lessOptions" value="<?php echo get_lang('LessElements'); ?>"><?php echo get_lang('LessElements'); ?></button>
	&nbsp;&nbsp;<button type="submit" class="plus" name="moreOptions" value="<?php echo get_lang('MoreElements'); ?>"><?php echo get_lang('MoreElements'); ?></button>
  </td>
</tr>
<tr>
  <td colspan="4">&nbsp;</td>
</tr>
<tr>
  <td colspan="4">
	<!-- <input type="submit" name="cancelAnswers" value="<?php echo get_lang('Cancel'); ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)); ?>')) return false;">
	&nbsp;&nbsp; //--> <input type="submit" name="submitAnswers" value="<?php echo get_lang('Ok'); ?>">
  </td>
</tr>
</table>
</form>

<?php
        } elseif ($answerType == HOT_SPOT || $answerType == HOT_SPOT_ORDER) {
            if($debug>0){echo str_repeat('&nbsp;',4).'$answerType is HOT_SPOT'."<br />\n";}
            $hotspot_colors = array("", // $i starts from 1 on next loop (ugly fix)
            						"#4271B5",
									"#FE8E16",
									"#45C7F0",
									"#BCD631",
									"#D63173",
									"#D7D7D7",
									"#90AFDD",
									"#AF8640",
									"#4F9242",
									"#F4EB24",
									"#ED2024",
									"#3B3B3B",
									"#F7BDE2");
?>

<h3>
  <?php echo get_lang('Question').": ".$questionName; ?>
</h3>
<?php
	if(!empty($msgErr))
	{
		Display::display_normal_message($msgErr); //main API
	}
?>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td colspan="2" style="border:1px solid #4271b5; border-bottom:none;"><?php echo get_lang('HotspotChoose'); ?></td>
	</tr>
	<tr>
		<td width="550" valign="top">
			<script type="text/javascript">
				<!--
				// Version check based upon the values entered above in "Globals"
				var hasReqestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);


				// Check to see if the version meets the requirements for playback
				if (hasReqestedVersion) {  // if we've detected an acceptable version
				    var oeTags = '<object type="application/x-shockwave-flash" data="../plugin/hotspot/hotspot_admin.swf?modifyAnswers=<?php echo $modifyAnswers ?>" width="550" height="377">'
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
		<td valign="top">
			<form method="post" id="frm_exercise" action="<?php echo api_get_self(); ?>?modifyAnswers=<?php echo $modifyAnswers; ?>" name="frm_exercise">
				<input type="hidden" name="formSent" value="1" />
				<input type="hidden" name="nbrAnswers" value="<?php echo $nbrAnswers; ?>" />
				<table border="0" cellpadding="3" cellspacing="0" style="border: 1px solid #4271b5; border-left:none; width: 100%; ">
					<!--
					<tr>
					  <td colspan="5"><?php echo get_lang('AnswerHotspot'); ?> :</td>
					</tr>
					-->
					<tr style="background-color: #E6E6E6; height: 37px">
					  <td style="width: 20px; border-bottom: 1px solid #4271b5">&nbsp;<?php /* echo get_lang('HotSpot'); */ ?></td>
					  <td style="width: 100px; border-bottom: 1px solid #4271b5"><?php echo get_lang('Description'); ?>*</td>
					  <td style="border-bottom: 1px solid #4271b5"><?php echo get_lang('Comment'); ?></td>
					  <td style="width: 60px; border-bottom: 1px solid #4271b5"><?php echo get_lang('QuestionWeighting'); ?>*</td>
					</tr>

					<?php
								for($i=1;$i <= $nbrAnswers;$i++)
								{
					?>

					<tr>
					  <td valign="top"><div style="height: 15px; width: 15px; background-color: <?php echo $hotspot_colors[$i]; ?>"> </div></td>
					  <td valign="top" align="left"><input type="text" name="reponse[<?php echo $i; ?>]" value="<?php echo api_htmlentities($reponse[$i],ENT_QUOTES,$charset); ?>" size="12" /></td>
					  <td align="left"><textarea wrap="virtual" rows="3" cols="10" name="comment[<?php echo $i; ?>]" style="width: 100%"><?php echo api_htmlentities($comment[$i],ENT_QUOTES,$charset); ?></textarea></td>
					  <td valign="top"><input type="text" name="weighting[<?php echo $i; ?>]" size="1" value="<?php echo (isset($weighting[$i]) ? $weighting[$i] : 1); ?>" />
					  <input type="hidden" name="hotspot_coordinates[<?php echo $i; ?>]" value="<?php echo (empty($hotspot_coordinates[$i]) ? '0;0|0|0' : $hotspot_coordinates[$i]); ?>" />
					  <input type="hidden" name="hotspot_type[<?php echo $i; ?>]" value="<?php echo (empty($hotspot_type[$i]) ? 'square' : $hotspot_type[$i]); ?>" /></td>
					</tr>

					<?php
					  			}
					?>

					<tr>
					  <td colspan="5">
						<input type="submit" name="lessAnswers" value="<?php echo get_lang('LessHotspots'); ?>" />
						<input type="submit" name="moreAnswers" value="<?php echo get_lang('MoreHotspots'); ?>" />
						<hr noshade="noshade" size="1" style="color: #4271b5" />
						<input type="submit" name="submitAnswers" value="<?php echo get_lang('Ok'); ?>" />
						<!--<input type="submit" name="cancelAnswers" value="<?php echo get_lang('Cancel'); ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)); ?>')) return false;" />-->
					  </td>
					</tr>
				</table>
			</form>
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td style="text-align:center; vertical-align:top; width:20px;">*</td>
					<td style="width:auto;"><?php echo get_lang('HotspotRequired'); ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>




<?php
        }
    }
    if($debug>0){echo str_repeat('&nbsp;',0).'$modifyAnswers was set - end'."<br />\n";}
}
?>
