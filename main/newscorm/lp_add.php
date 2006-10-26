<?php //$id: $
/**
 * Script included in lp_list.php to show the form for the addition of a new LP
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Script using variables and functions supposed to be available from lp_list.php
 */
?>
<form name="form1" method="post" action="lp_controller.php">
<h4><?php echo get_lang('_add_learnpath'); ?></h4>
<table width="400" border="0" cellspacing="2" cellpadding="0">
	<tr>
		<td align="right"><?php echo get_lang('_title');?></td>
		<td>
			<input name="learnpath_name" type="text" value="" size="50" />
		</td>
	</tr>
	<?php if($show_description_field){ ?>
	<tr>
		<td align="right" valign="top"><?php echo get_lang('_description');?></td>
		<td><textarea name='learnpath_description' cols='45'></textarea></td>
	</tr>
	<?php } ?>
	<tr>
		<td align="right">&nbsp;
			<input type="hidden" name='action' value='add_lp' />
		</td>
		<td>
			<input type="submit" name="submit_button" value="<?php echo get_lang('Ok'); ?>" />
		</td>
	</tr>
</table>
</form>