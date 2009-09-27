<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) 2009 Julio Montoya Armas <gugli100@gmail.com>

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
*	This script allows platform admins to add users to urls.
*	It displays a list of users and a list of courses;
*	you can select multiple users and courses and then click on
*	@package dokeos.admin
==============================================================================
*/

// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require ('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
api_protect_admin_script();
if (!$_configuration['multiple_access_urls'])
	header('Location: index.php');

/*
-----------------------------------------------------------
	Global constants and variables
-----------------------------------------------------------
*/

$form_sent = 0;
$first_letter_session = '';
$sessions = array ();
$url_list = array();
$users = array();

$tbl_access_url_rel_session = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
$tbl_access_url 			= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_session				= Database :: get_main_table(TABLE_MAIN_SESSION);

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
$tool_name = get_lang('AddSessionToURL');
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'access_urls.php', 'name' => get_lang('MultipleAccessURLs'));

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

Display :: display_header($tool_name);
echo '<div class="actions" style="height:22px;">';
echo '<div style="float:right;">
		<a href="'.api_get_path(WEB_CODE_PATH).'admin/access_url_edit_sessions_to_url.php">'.Display::return_icon('course_del.gif',get_lang('EditSessionToURL'),'').get_lang('EditSessionToURL').'</a>
	  </div><br />';
echo '</div>';

api_display_tool_title($tool_name);

if ($_POST['form_sent']) {
	$form_sent = $_POST['form_sent'];
	$sessions = is_array($_POST['session_list']) ? $_POST['session_list'] : array() ;
	$url_list = is_array($_POST['url_list']) ? $_POST['url_list'] : array() ;
	$first_letter_session = $_POST['first_letter_session'];

	foreach($users as $key => $value) {
		$users[$key] = intval($value);
	}

	if ($form_sent == 1) {
		if ( count($sessions) == 0 || count($url_list) == 0) {
			Display :: display_error_message(get_lang('AtLeastOneSessionAndOneURL'));
			//header('Location: access_urls.php?action=show_message&message='.get_lang('AtLeastOneUserAndOneURL'));
		} else {
			UrlManager::add_sessions_to_urls($sessions,$url_list);
			Display :: display_confirmation_message(get_lang('SessionBelongURL'));
			//header('Location: access_urls.php?action=show_message&message='.get_lang('UsersBelongURL'));
		}
	}
}



/*
-----------------------------------------------------------
	Display GUI
-----------------------------------------------------------
*/
/*
if(empty($first_letter_user)) {
	$sql = "SELECT count(*) as num_courses FROM $tbl_course";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$num_row = Database::fetch_array($result);
	if($num_row['num_courses']>1000)
	{//if there are too much num_courses to gracefully handle with the HTML select list,
	 // assign a default filter on users names
		$first_letter_user = 'A';
	}
	unset($result);
}
*/
$first_letter_session = Database::escape_string($first_letter_session);
$sql = "SELECT id, name FROM $tbl_session
		WHERE name LIKE '".$first_letter_session."%' OR name LIKE '".api_strtolower($first_letter_session)."%'
		ORDER BY name DESC ";

$result = api_sql_query($sql, __FILE__, __LINE__);
$db_sessions = Database::store_result($result);
unset($result);

$sql = "SELECT id, url FROM $tbl_access_url  WHERE active=1 ORDER BY url";
$result = api_sql_query($sql, __FILE__, __LINE__);
$db_urls = Database::store_result($result);
unset($result);
?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;">
 <input type="hidden" name="form_sent" value="1"/>
  <table border="0" cellpadding="5" cellspacing="0" width="100%">
   <tr>
    <td width="40%" align="center">
     <b><?php echo get_lang('SessionList'); ?></b>
     <br/><br/>
     <?php echo get_lang('FirstLetterSession'); ?> :
     <select name="first_letter_session" onchange="javascript:document.formulaire.form_sent.value='2'; document.formulaire.submit();">
      <option value="">--</option>
      <?php
        echo Display :: get_alphabet_options($first_letter_session);
        echo Display :: get_numeric_options(0,9,$first_letter_session);
      ?>
     </select>
    </td>
        <td width="20%">&nbsp;</td>
    <td width="40%" align="center">
     <b><?php echo get_lang('URLList'); ?> :</b>
    </td>
   </tr>
   <tr>
    <td width="40%" align="center">
     <select name="session_list[]" multiple="multiple" size="20" style="width:230px;">
		<?php

		foreach ($db_sessions as $session) {
			?>
			<option value="<?php echo $session['id']; ?>"
			<?php if(in_array($session['id'],$sessions))
			echo 'selected="selected"'; ?>>
			<?php echo $session['name']; ?></option>
			<?php
		}
		?>
    </select>
   </td>
   <td width="20%" valign="middle" align="center">
    <input type="submit" value="<?php echo get_lang('AddSessionsToThatURL'); ?> &gt;&gt;"/>
   </td>
   <td width="40%" align="center">
    <select name="url_list[]" multiple="multiple" size="20" style="width:230px;">
		<?php
		foreach ($db_urls as $url_obj) {
			?>
			<option value="<?php echo $url_obj['id']; ?>" <?php if(in_array($url_obj['id'],$url_list)) echo 'selected="selected"'; ?>><?php echo $url_obj['url']; ?></option>
			<?php
		}
		?>
    </select>
   </td>
  </tr>
 </table>
</form>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>