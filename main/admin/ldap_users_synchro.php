<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.admin
 */
/**
 * Code.
 */
exit(); //not yet functional, needs to be revised

$cidReset = true;

require '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require "../auth/ldap/authldap.php";
$annee_base = date('Y');
// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', "name" => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => api_get_self(), "name" => get_lang('SessionsList')];

$id_session = intval($_GET['id_session']);

$formSent = 0;
$errorMsg = $firstLetterUser = $firstLetterSession = '';
$UserList = $SessionList = [];
$users = $sessions = [];
$page = intval($_GET['page']);
$action = $_REQUEST['action'];

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
if (isset($action) && ($action == "synchro")) {
    $included = true;
    require 'ldap_synchro.php';
    echo Display::return_message($message, 'normal', false);
}
Display::display_footer();
?>
