<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows creating audio files from a text.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Raña Trabado
 * @since 8/janvier/2011
 * TODO:clean all file and check lang for languages
*/

/*	INIT SECTION */

// Name of the language file that needs to be included
$language_file = array('document');

require_once '../inc/global.inc.php';
$_SESSION['whereami'] = 'document/createaudio';
$this_section = SECTION_COURSES;

require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

$nameTools = get_lang('CreateAudio');

api_protect_course_script();
api_block_anonymous_users();
if (api_get_setting('enabled_text2audio') == 'false'){
	api_not_allowed(true);
}
if (!isset($_GET['dir'])){
	api_not_allowed(true);
}

$dir = isset($_GET['dir']) ? Security::remove_XSS($_GET['dir']) : Security::remove_XSS($_POST['dir']);
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

// Please, do not modify this dirname formatting

if (strstr($dir, '..')) {
	$dir = '/';
}

if ($dir[0] == '.') {
	$dir = substr($dir, 1);
}

if ($dir[0] != '/') {
	$dir = '/'.$dir;
}

if ($dir[strlen($dir) - 1] != '/') {
	$dir .= '/';
}

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$dir;

if (!is_dir($filepath)) {
	$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
	$dir = '/';
}

//groups //TODO: clean
if (isset ($_SESSION['_gid']) && $_SESSION['_gid'] != 0) {
		$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
		$interbreadcrumb[] = array ("url" => "../group/group_space.php?gidReq=".$_SESSION['_gid'], "name" => get_lang('GroupSpace'));
		$noPHP_SELF = true;
		$to_group_id = $_SESSION['_gid'];
		$group = GroupManager :: get_group_properties($to_group_id);
		$path = explode('/', $dir);
		if ('/'.$path[1] != $group['directory']) {
			api_not_allowed(true);
		}
}

$interbreadcrumb[] = array ("url" => "./document.php?curdirpath=".urlencode($_GET['dir']).$req_gid, "name" => get_lang('Documents'));

if (!$is_allowed_in_course) {
	api_not_allowed(true);
}


if (!($is_allowed_to_edit || $_SESSION['group_member_with_upload_rights'] || is_my_shared_folder($_user['user_id'], Security::remove_XSS($_GET['dir']),api_get_session_id()))) {
	api_not_allowed(true);
}


/*	Header */
event_access_tool(TOOL_DOCUMENT);
$display_dir = $dir;
if (isset ($group)) {
	$display_dir = explode('/', $dir);
	unset ($display_dir[0]);
	unset ($display_dir[1]);
	$display_dir = implode('/', $display_dir);
}

// Interbreadcrumb for the current directory root path
	// Copied from document.php
	$dir_array = explode('/', $dir);
	$array_len = count($dir_array);
	
	
	$dir_acum = '';
	for ($i = 0; $i < $array_len; $i++) {
		$url_dir = 'document.php?&curdirpath='.$dir_acum.$dir_array[$i];
		//Max char 80
		$url_to_who = cut($dir_array[$i],80);
		if ($is_certificate_mode) {
			$interbreadcrumb[] = array('url' => $url_dir.'&selectcat='.Security::remove_XSS($_GET['selectcat']), 'name' => $url_to_who);
		} else {
			$interbreadcrumb[] = array('url' => $url_dir, 'name' => $url_to_who);
		}
		$dir_acum .= $dir_array[$i].'/';
	}
//
Display :: display_header($nameTools, 'Doc');

echo '<div class="actions">';
		echo '<a href="document.php?curdirpath='.Security::remove_XSS($_GET['dir']).'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview')).get_lang('BackTo').' '.get_lang('DocumentsOverview').'</a>';
echo '</div>';


?>
<div align="center">
<?php Display::display_icon('sound.gif', get_lang('CreateAudio')); echo get_lang('HelpText2Audio'); ?>
<form id="form1" name="form1" method="post" action="http://vozme.com/text2voice.php" target="mymp3" class="formw">
  <p>
    <label><?php echo get_lang('Language')?>: 
      <select name="lang" id="select">
        <option value="en" selected="selected">English</option>
        <option value="es">Español</option>
        <option value="pt">Português</option>
        <option value="it">Italiano</option>        
        <option value="ca">Català</option>
        <option value="hi">हिन्दी</option>
      </select>
    </label>
    <label><?php echo get_lang('Voice')?>:
      <select name="gn" id="select1">
        <option value="ml"><?php echo get_lang('Male')?></option>
        <option value="fm"><?php echo get_lang('Female')?></option>
      </select>
    </label>
  </p>
  <div><?php echo get_lang('InsertText2Audio')?></div>
  <p>
    <label>
      <textarea name="text" id="textarea" cols="55" rows="6"></textarea>
    </label>
  </p>
  <p>
    <button class="save" type="submit" name="SendText2Audio"><?php echo get_lang('BuildMP3');?></button>
  </p>
</form>
</div>
<?php
Display :: display_footer();
?>