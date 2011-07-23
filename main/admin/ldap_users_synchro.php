<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.admin
 */
/**
 * Code
 */
exit(); //not yet functional, needs to be revised
// name of the language file that needs to be included
$language_file='admin';

$cidReset=true;

require('../inc/global.inc.php');
$libpath = api_get_path(LIBRARY_PATH);
require_once($libpath.'formvalidator/FormValidator.class.php');
require_once($libpath.'usermanager.lib.php');
require("../auth/ldap/authldap.php");
$annee_base=date('Y');
// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => api_get_self(),"name" => get_lang('SessionsList'));

// Database Table Definitions
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_class				= Database::get_main_table(TABLE_MAIN_SESSION_CLASS);
$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_user							= Database::get_main_table(TABLE_MAIN_USER);
$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_class							= Database::get_main_table(TABLE_MAIN_CLASS);
$tbl_class_user						= Database::get_main_table(TABLE_MAIN_CLASS_USER);

$tbl_session_rel_etape 				= "session_rel_etape";

$id_session=intval($_GET['id_session']);

$formSent=0;
$errorMsg=$firstLetterUser=$firstLetterSession='';
$UserList=$SessionList=array();
$users=$sessions=array();
$noPHP_SELF=true;

$page=intval($_GET['page']);
$action=$_REQUEST['action'];

$tool_name = get_lang('Synchro LDAP : Import Etudiants/Etapes dans session');
Display::display_header($tool_name);
//api_display_tool_title($tool_name);

?>
		<form method="get" action="<?php echo api_get_self(); ?>" onsubmit="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">
			<select name="action">
			<option value="synchro"><?php echo get_lang('Synchro LDAP : Import Etudiants/Etapes dans toutes les sessions'); ?></option>
			</select>
			<input type="submit" value="<?php echo get_lang('Ok'); ?>">
		</form>

<?php
if (isset($action) && ($action=="synchro")) {
	$included=true;
	require('ldap_synchro.php');
	Display :: display_normal_message($message,false);
}
Display::display_footer();
?>
