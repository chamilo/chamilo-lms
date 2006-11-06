<?php // $Id: exercise.lib.php 9866 2006-11-06 09:43:17Z bmol $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
		EXERCISE TOOL LIBRARY
 *
 * shows a question and its answers
 *
 * @returns 'number of answers' if question exists, otherwise false
 *
 * @author Olivier Brouckaert <oli.brouckaert@skynet.be>
 *
 * @param integer	$questionId		ID of the question to show
 * @param boolean	$onlyAnswers	set to true to show only answers
 *	@package dokeos.exercise
 ==============================================================================
 */
require("../inc/lib/fckeditor/fckeditor.php") ;
function showQuestion($questionId, $onlyAnswers=false, $origin=false)
{
	// construction of the Question object
	$objQuestionTmp=new Question();

	// reads question informations
	if(!$objQuestionTmp->read($questionId))
	{
		// question not found
		return false;
	}

	$answerType=$objQuestionTmp->selectType();
	$pictureName=$objQuestionTmp->selectPicture();

	if ($answerType != HOT_SPOT) // Question is not of type hotspot
	{

		if(!$onlyAnswers)
		{
			$questionName=$objQuestionTmp->selectTitle();
			$questionDescription=$objQuestionTmp->selectDescription();

		$s="<tr>
		  <td valign='top' colspan='2'>";
		$questionName=api_parse_tex($questionName);
		$s.=$questionName;
		$s.="</td>
		</tr>
		<tr>
		  <td valign='top' colspan='2'>
			<i>";
		$questionDescription=api_parse_tex($questionDescription);
		$s.=$questionDescription;
		$s.="</i>
		  </td>
		</tr>";

		if(!empty($pictureName))
			{
			$s.="
		<tr>
		  <td align='center' colspan='2'><img src='../document/download.php?doc_url=%2Fimages%2F'".$pictureName."' border='0'></td>
		</tr>";
			}

		}  // end if(!$onlyAnswers)

		// construction of the Answer object
		$objAnswerTmp=new Answer($questionId);

		$nbrAnswers=$objAnswerTmp->selectNbrAnswers();

		// only used for the answer type "Matching"
		if($answerType == MATCHING)
		{
			$cpt1='A';
			$cpt2=1;
			$Select=array();
		}
		elseif($answerType == FREE_ANSWER)
			{
	        #$comment = $objAnswerTmp->selectComment(1);
	        //
	$upload_path = api_get_path(REL_COURSE_PATH).$_SESSION['_course']['path'].'/document/';
	$oFCKeditor = new FCKeditor("choice[".$questionId."]") ;
	$oFCKeditor->BasePath = api_get_path(WEB_LIBRARY_PATH)."fckeditor/";
	//$oFCKeditor->Config['CustomConfigurationsPath'] = api_get_path(WEB_PATH)."claroline/inc/lib/fckeditor_new/myconfig.js?".time(); //to clear cache we use time() but always clear history manually
	/*
	$oFCKeditor->Config['ImageBrowserURL'] = $oFCKeditor->BasePath . "editor/filemanager/browser/default/browser.html?Type=Image&Connector=connectors/php/connector.php&ServerPath=/$upload_path/";

	$oFCKeditor->Config['ImageUploadURL'] = $oFCKeditor->BasePath . "editor/filemanager/upload/php/upload.php?Type=Image&ServerPath=/$upload_path/" ;

			//for Link/File
			$oFCKeditor->Config['LinkBrowserURL'] = $oFCKeditor->BasePath . "editor/filemanager/browser/default/browser.html?Connector=connectors/php/connector.php&ServerPath=$upload_path";

			$oFCKeditor->Config['LinkUploadURL'] = $oFCKeditor->BasePath . "editor/filemanager/upload/php/upload.php?ServerPath=$upload_path" ;

			//for image
			$oFCKeditor->Config['ImageBrowserURL'] = $oFCKeditor->BasePath . "editor/filemanager/browser/default/browser.html?Type=Image&Connector=connectors/php/connector.php&ServerPath=$upload_path";

			$oFCKeditor->Config['ImageUploadURL'] = $oFCKeditor->BasePath . "editor/filemanager/upload/php/upload.php?Type=Image&ServerPath=$upload_path" ;

			//for flash
			$oFCKeditor->Config['FlashBrowserURL'] = $oFCKeditor->BasePath . "editor/filemanager/browser/default/browser.html?Type=Flash&Connector=connectors/php/connector.php&ServerPath=$upload_path";

			$oFCKeditor->Config['FlashUploadURL'] = $oFCKeditor->BasePath . "editor/filemanager/upload/php/upload.php?Type=Flash&ServerPath=$upload_path" ;

			//for MP3
			$oFCKeditor->Config['MP3BrowserURL'] = $oFCKeditor->BasePath . "editor/filemanager/browser/default/browser.html?Type=MP3&Connector=connectors/php/connector.php&ServerPath=$upload_path";

			$oFCKeditor->Config['MP3UploadURL'] = $oFCKeditor->BasePath . "editor/filemanager/upload/php/upload.php?Type=MP3&ServerPath=$upload_path" ;

			//for other media
			$oFCKeditor->Config['VideoBrowserURL'] = $oFCKeditor->BasePath . "editor/filemanager/browser/default/browser.html?Type=Video&Connector=connectors/php/connector.php&ServerPath=$upload_path";

			$oFCKeditor->Config['VideoUploadURL'] = $oFCKeditor->BasePath . "editor/filemanager/upload/php/upload.php?Type=Video&ServerPath=$upload_path" ;

	$oFCKeditor->ToolbarSet = 'Comment' ;*/
	$oFCKeditor->Width  = '70%';
	$oFCKeditor->Height = '150';
	$oFCKeditor->Value	= '' ;
	$s .= "<tr><td colspan='2'>".$oFCKeditor->CreateHtml()."</td></tr>";
			//$s.="<tr><td colspan='2'><textarea cols='80' rows='10' name='choice[".$questionId."]'>$answer</textarea></td></tr>";

			}

		for($answerId=1;$answerId <= $nbrAnswers;$answerId++)
		{
			$answer=$objAnswerTmp->selectAnswer($answerId);
			$answerCorrect=$objAnswerTmp->isCorrect($answerId);

			if($answerType == FILL_IN_BLANKS)
			{
				// splits text and weightings that are joined with the character '::'
				list($answer)=explode('::',$answer);

				// because [] is parsed here we follow this procedure:
				// 1. find everything between the [tex] and [/tex] tags
				$startlocations=strpos($answer,'[tex]');
				$endlocations=strpos($answer,'[/tex]');

				if($startlocations !== false && $endlocations !== false)
				{
					$texstring=substr($answer,$startlocations,$endlocations-$startlocations+6);
					// 2. replace this by {texcode}
					$answer=str_replace($texstring,'{texcode}',$answer);
				}

				// 3. do the normal matching parsing

				// replaces [blank] by an input field
				$answer=ereg_replace('\[[^]]+\]','<input type="text" name="choice['.$questionId.'][]" size="10">',nl2br($answer));
				// 4. replace the {texcode by the api_pare_tex parsed code}
				$texstring = api_parse_tex($texstring);
				$answer=str_replace("{texcode}",$texstring,$answer);
			}

			// unique answer
			if($answerType == UNIQUE_ANSWER)
			{
			$s.="
			<tr>
			  <td width='5%' align='center'>
				<input class='checkbox' type='radio' name='choice[".$questionId."]' value='".$answerId."'>
			  </td>
			  <td width='95%'>";
			$answer=api_parse_tex($answer);
			$s.=$answer;
			$s.="</td></tr>";

			}
			// multiple answers
			elseif($answerType == MULTIPLE_ANSWER)
			{
			$s.="<tr>
			  <td width='5%' align='center'>
			<input class='checkbox' type='checkbox' name='choice[".$questionId."][".$answerId."]' value='1'>
			  </td>
			  <td width='95%'>";
			$answer = api_parse_tex($answer);
			$s.=$answer;
			$s.="</td></tr>";

			}
			// fill in blanks
			elseif($answerType == FILL_IN_BLANKS)
			{
			$s.="<tr><td colspan='2'>$answer</td></tr>";

			}
			// free answer

			// matching
			else
			{
				if(!$answerCorrect)
				{
					// options (A, B, C, ...) that will be put into the list-box
					$Select[$answerId]['Lettre']=$cpt1++;
					// answers that will be shown at the right side
					$answer = api_parse_tex($answer);
					$Select[$answerId]['Reponse']=$answer;
				}
				else
				{
		$s.="
		<tr>
		  <td colspan='2'>
			<table border='0' cellpadding='0' cellspacing='0' width='100%'>
			<tr>";
		$answer=api_parse_tex($answer);
		$s.="<td width='40%' valign='top'><b>".$cpt2."</b>.&nbsp;".$answer."</td>
			  <td width='20%' align='center'>&nbsp;&nbsp;<select name='choice[".$questionId."][".$answerId."]'>
				<option value='0'>--</option>";

		            // fills the list-box
		            foreach($Select as $key=>$val)
		            {

						$s.="<option value='".$key."'>".$val['Lettre']."</option>";

					}  // end foreach()

		$s.="</select>&nbsp;&nbsp;</td>
			  <td width='40%' valign='top'>";
		if(isset($Select[$cpt2])) $s.='<b>'.$Select[$cpt2]['Lettre'].'.</b> '.$Select[$cpt2]['Reponse'];
			else $s.='&nbsp;';
		$s.="
		</td>
			</tr>
			</table>
		  </td>
		</tr>";

					$cpt2++;

					// if the left side of the "matching" has been completely shown
					if($answerId == $nbrAnswers)
					{
						// if it remains answers to shown at the right side
						while(isset($Select[$cpt2]))
						{


		$s.="<tr>
		  <td colspan='2'>
			<table border='0' cellpadding='0' cellspacing='0' width='100%'>
			<tr>
			  <td width='60%' colspan='2'>&nbsp;</td>
			  <td width='40%' valign='top'>";
		$s.='<b>'.$Select[$cpt2]['Lettre'].'.</b> '.$Select[$cpt2]['Reponse'];
		$s.="</td>
			</tr>
			</table>
		  </td>
		</tr>";


							$cpt2++;
						}	// end while()
					}  // end if()
				}
			}
		}	// end for()

		// destruction of the Answer object
		unset($objAnswerTmp);

		// destruction of the Question object
		unset($objQuestionTmp);

		if ($origin != 'export')
		{
			echo $s;
		}
		else
		{
			return($s);
		}
	}
	elseif ($answerType == HOT_SPOT) // Question is of type HOT_SPOT
	{
		$questionName=$objQuestionTmp->selectTitle();
		$questionDescription=$objQuestionTmp->selectDescription();

		// Get the answers, make a list
		$objAnswerTmp=new Answer($questionId);
		$nbrAnswers=$objAnswerTmp->selectNbrAnswers();

		$answer_list = '<div style="padding: 10px; margin-left: -8px; border: 1px solid #4271b5; height: 448px; width: 200px;"><b>'.get_lang('langHotspotZones').'</b><ol>';
		for($answerId=1;$answerId <= $nbrAnswers;$answerId++)
		{
			$answer_list .= '<li>'.$objAnswerTmp->selectAnswer($answerId).'</li>';
		}
		$answer_list .= '</ol></div>';

		if(!$onlyAnswers)
		{
			$s="<tr>
			  <td valign='top' colspan='2'>";
			$questionName=api_parse_tex($questionName);
			$s.=$questionName;
			$s.="</td>
			</tr>
			<tr>
			  <td valign='top' colspan='2'>
				<i>";
			$questionDescription=api_parse_tex($questionDescription);
			$s.=$questionDescription;
			$s.="</i>
			  </td>
			</tr>";
		}

		$canClick = isset($_GET['editQuestion']) ? '0' : (isset($_GET['modifyAnswers']) ? '0' : '1');
		//$tes = isset($_GET['modifyAnswers']) ? '0' : '1';
		//echo $tes;
		$s .= '<tr><td valign="top" colspan="2"><table><tr><td>'."
					<script language=\"JavaScript\" type=\"text/javascript\">
						<!--
						// Version check based upon the values entered above in \"Globals\"
						var hasReqestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);


						// Check to see if the version meets the requirements for playback
						if (hasReqestedVersion) {  // if we've detected an acceptable version
						    var oeTags = '<object type=\"application/x-shockwave-flash\" data=\"../plugin/hotspot/hotspot_user.swf?modifyAnswers=".$questionId."&amp;canClick:".$canClick."\" width=\"380\" height=\"470\">'
										+ '<param name=\"movie\" value=\"../plugin/hotspot/hotspot_user.swf?modifyAnswers=".$questionId."&amp;canClick:".$canClick."\" \/>'
										+ '<\/object>';
						    document.write(oeTags);   // embed the Flash Content SWF when all tests are passed
						} else {  // flash is too old or we can't detect the plugin
							var alternateContent = 'Error<br \/>'
								+ 'Hotspots requires Macromedia Flash 7.<br \/>'
								+ '<a href=http://www.macromedia.com/go/getflash/>Get Flash<\/a>';
							document.write(alternateContent);  // insert non-flash content
						}
						// -->
					</script></td>
					<td valign='top'>$answer_list</td></tr></table>
		</td></tr>";
		echo $s;

	}

	return $nbrAnswers;
}
?>