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
* 	@version $Id: blank.php 10680 2007-01-11 21:26:23Z pcool $
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

<table width="707" border="0" cellspacing="0" cellpadding="0">
	<tr>
    <?php 
		switch ($qtype)
		{
			/** @todo switch statement with hard coded language string => won't work in a language that is not English */
			case "Yes/No":
			{
				?>
				<td width="100%" bgcolor="F6F5F5"><p><strong><?php echo get_lang('Question'); ?>: </strong><br />
	            <?php echo html_entity_decode($ques);?><br />
	            <strong><?php echo get_lang('Answers'); ?>:</strong>><br />
				<textarea cols="50" rows="3" disabled='true'><?php echo $answers[0];?></textarea>
	            <input name="radiobutton" type="radio" value="radiobutton">
				<textarea cols="50" rows="3" disabled='true'><?php echo $answers[1];?></textarea>
	            <input name="radiobutton" type="radio" value="radiobutton">
				</td>
				
				<td width="10" height="161" bgcolor="F6F5F5">&nbsp;</td>
				<?php 
				break;
			}
			
			/** @todo switch statement with hard coded language string => won't work in a language that is not English */
			case "Multiple Choice (multiple answer)":
			{
				?>
	        	<td width="100%" bgcolor="F6F5F5"><strong><?php echo get_lang('Question'); ?>:</strong><br />
	            <?php echo $ques;?><br />
				<br />
				<strong><?php echo get_lang('Answers'); ?>: </strong><br />
				<?php 
				$i=0;
				for($p=1;$p<$count;$i++,$p++)
				{
				?>
					<textarea cols="50" rows="3" disabled="true"><?php echo $answers[$i]; ?></textarea>
					<input type="checkbox" name="checkbox" value="checkbox">
					<br />
				<?php 
				}
				?>
				</td>
	          	<td width="8" height="161" bgcolor="F6F5F5">&nbsp;</td> 
	          	<?php 
				break;
			}
			
			/** @todo switch statement with hard coded language string => won't work in a language that is not English */
			case "Multiple Choice (single answer)":
			{
				?>
	          	<td width="100%" bgcolor="F6F5F5"><strong><?php echo get_lang('Question'); ?>:</strong><br />
	                <?php echo $ques;?><br />
	                <br />
	                <strong><?php echo get_lang('Answers'); ?>:</strong><br />
					<?php 
					$i=0;
					for($p=1;$p<$count;$i++,$p++)
					{
					?>
	              		<textarea cols="50" rows="3" disabled='true'><?php echo $answers[$i]; ?></textarea>
	              		<input name="radiobutton" type="radio" value="radiobutton">
	              		<br />
	              	<?php 
					}
					?>
	          		</td>
	          		<td width="8" height="161" bgcolor="F6F5F5">&nbsp;</td>
	          		<?php 
					break;
			}
			
			/** @todo switch statement with hard coded language string => won't work in a language that is not English */
			case "Open":
			{
				?>
	          	<td width="87" bgcolor="F6F5F5"><strong><?php echo get_lang('Question'); ?>:</strong><br />
				<?php echo $ques;?><br />
	            <br />
	            <strong><?php echo get_lang('Answers'); ?>:</strong><br />
	            <textarea  style="width: 100%" name="defaultext" rows=3 cols=60></textarea>
	          	</td>
	          	<?php 
				break;
			}
			
			/** @todo switch statement with hard coded language string => won't work in a language that is not English */
			case "Numbered":
			{
				?>
	           	<td width="144" bgcolor="F6F5F5"><strong><?php echo get_lang('Question'); ?>:</strong><br />
				<?php echo $ques;?><br />
	            <br />
	            <strong><?php echo get_lang('Answers'); ?>: </strong><br />
				<?php 
				$i=0;
				for($p=1;$p<$count;$i++,$p++)
				{
					?>
					<textarea cols="50" rows="3" disabled='true'><?php echo $answers[$i]; ?></textarea>
					<select>
						<option value="not applicable"><?php echo get_lang('NotApplicable'); ?></option>
						<option value="<?php echo $i; ?>">1</option>
		                <option value="<?php echo $i; ?>">2</option>
		                <option value="<?php echo $i; ?>">3</option>
		                <option value="<?php echo $i; ?>">4</option>
		                <option value="<?php echo $i; ?>">5</option>
		                <option value="<?php echo $i; ?>">6</option>
		                <option value="<?php echo $i; ?>">7</option>
		                <option value="<?php echo $i; ?>">8</option>
		                <option value="<?php echo $i; ?>">9</option>
		                <option value="<?php echo $i; ?>">10</option>
					</select>
					<br />
		            <?php 
				}
	          	echo '</td>';
				break;		
			}
		}
		?>
        </tr>
      </table>
<?php 
Display :: display_footer();
?>
