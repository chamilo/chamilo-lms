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
*	@package dokeos.survey
* 	@author 
* 	@version $Id: bluebreeze.php 10680 2007-01-11 21:26:23Z pcool $
*/

// name of the language file that needs to be included 
$language_file = 'survey';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");

/** @todo replace this with the correct code */
/*
$status = surveymanager::get_status();
api_protect_course_script();
if($status==5)
{
	api_protect_admin_script();
}
*/
/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit())
{
	Display :: display_header();
	Display :: display_error_message(get_lang('NotAllowedHere'));
	Display :: display_footer();
	exit;
}

// $_GET and $_POST
/** @todo replace $_REQUEST with $_GET or $_POST */
$temp = $_REQUEST['temp'];
$ques = $_REQUEST['ques'];
$ans = $_REQUEST['ans'];
$qtype = $_REQUEST['qtype'];

$answers = explode("|",$ans);
$count = count($answers);

Display :: display_header();
?>
<table width="600" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="23" height="21"><img src="../survey/images_white/top-1.gif" width="23" height="21"></td>
		<td height="21" background="../survey/images_white/top-2.gif">&nbsp;</td>
		<td width="20" height="21"><img src="../survey/images_white/top-3.gif" width="20" height="21"></td>
	</tr>
	<tr>
    <?
    switch ($qtype)
	{
		/** @todo switch statement with hard coded language string => won't work in a language that is not English */
		case "Yes/No":
		{
		?>
			<td height="39" background="images_white/left.gif">&nbsp;</td>
          	<td><strong><?php echo get_lang('Question'); ?>: </strong><br />
          	<?php echo $ques;?><br /><br /><strong><?php echo get_lang('Answers'); ?>: </strong><br />
          	<textarea cols="50" rows="3" disabled='true'><?php echo $answers[0];?></textarea>
          	<input name="radiobutton" type="radio" value="radiobutton"><br /><textarea cols="50" rows="3" disabled='true'><?php echo $answers[1];?></textarea>
          	<input name="radiobutton" type="radio" value="radiobutton"></td>
          	<td background="images_white/right.gif">&nbsp;</td>
			<?php 
			break;
		}
		
		/** @todo switch statement with hard coded language string => won't work in a language that is not English */
		case "Multiple Choice (multiple answer)":
		{
		?>
			<td height="39" background="images_white/left.gif">&nbsp;</td>
        	<td><strong><?php echo get_lang('Question'); ?>: </strong><br />
        	<?php echo $ques;?><br />
        	<br /><strong><?php echo get_lang('Answers'); ?>: </strong><br />
			<?php 
			$i=0;
			for($p=1;$p<$count;$i++,$p++)
			{
			?>
				<textarea cols="50" rows="3" disabled='true'><?php echo $answers[$i]; ?></textarea>
				<input type="checkbox" name="checkbox" value="checkbox"><br />
			<?php 
			}
			?>
			</td>
        	<td background="images_white/right.gif">&nbsp;</td>
			<?php 
			break;
		}
		
		/** @todo switch statement with hard coded language string => won't work in a language that is not English */
		case "Multiple Choice (single answer)":
		{?>
		<td height="39" background="../survey/images_white/left.gif">&nbsp;</td>
        <td><strong><?php echo get_lang('Question'); ?>: </strong><br /><?php echo $ques;?><br /><br /><strong><?php echo get_lang('Answers'); ?>: </strong><br />
		<?php 
		$i=0;
		for($p=1;$p<$count;$i++,$p++)
		{
		?>
		<textarea cols="50" rows="3" disabled='true'><?php echo $answers[$i]; ?></textarea>
		<input name="radiobutton" type="radio" value="radiobutton"><br />
		<?php 
		}
		?>
		</td>
        <td background="../survey/images_white/right.gif">&nbsp;</td>
		<?php 
		break;
		}
		case "Open":
		{?>
		<td height="39" background="../survey/images_white/left.gif">&nbsp;</td>
        <td><strong><?php echo get_lang('Question'); ?>: </strong><br /><?php echo $ques;?><br /><br /><strong>Answer: </strong><br />
		<TEXTAREA  style="WIDTH: 100%" name="defaultext" rows=3 cols=60>
        </TEXTAREA> 	
		</td>
        <td background="../survey/images_white/right.gif">&nbsp;</td>
		<?php 
		break;
		}
		case "Numbered":
		{?>
		<td height="39" background="../survey/images_white/left.gif">&nbsp;</td>
        <td><strong><?php echo get_lang('Question'); ?>: </strong><br /><?php echo $ques;?><br /><br /><strong><?php echo get_lang('Answers'); ?>: </strong><br />
		<?php 
		$i=0;
		for($p=1;$p<$count;$i++,$p++)
		{
		?>
		<textarea cols="50" rows="3" disabled='true'><?php echo $answers[$i]; ?></textarea>
		<select>
		<option value="not applicable">Not Applicable</option>
		<option value="$i">1</option>
		<option value="$i">2</option>
		<option value="$i">3</option>
		<option value="$i">4</option>
		<option value="$i">5</option>
		<option value="$i">6</option>
		<option value="$i">7</option>
		<option value="$i">8</option>
		<option value="$i">9</option>
		<option value="$i">10</option>
		</select><br />
		<?php 
		}
		?>
		</td>
        <td background="../survey/images_white/right.gif">&nbsp;</td>
		<?php 
		break;		
		}
		}
		
		?>
          
        </tr>
        <tr>
          <td background="../survey/images_white/left.gif">&nbsp;</td>
          <td><p>&nbsp;</p>
              </td>
          <td background="../survey/images_white/right.gif">&nbsp;</td>
        </tr>
        <tr>
          <td><img src="../survey/images_white/bottom-1.gif" width="23" height="21"></td>
          <td background="../survey/images_white/bottom-2.gif">&nbsp;</td>
          <td><img src="../survey/images_white/bottom-3.gif" width="20" height="21"></td>
        </tr>
      </table>
<?php
Display :: display_footer();
?>
