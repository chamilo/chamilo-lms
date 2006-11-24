<?php
$langFile='admin';

$cidReset=true;

include('../inc/global.inc.php');

api_protect_admin_script();

// table definitions
$tbl_user=Database::get_main_table(TABLE_MAIN_USER);
$tbl_course=Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_session=Database::get_main_table(MAIN_SESSION_TABLE);
$tbl_session_rel_course=Database::get_main_table(MAIN_SESSION_COURSE_TABLE);
$tbl_session_rel_course_rel_user=Database::get_main_table(MAIN_SESSION_COURSE_USER_TABLE);

$id_session=intval($_GET['id_session']);
$course_code=trim(stripslashes($_GET['course_code']));
$page=intval($_GET['page']);
$action=$_REQUEST['action'];
$sort=in_array($_GET['sort'],array('lastname','firstname','username'))?$_GET['sort']:'lastname';

$result=api_sql_query("SELECT name,title FROM $tbl_session,$tbl_course WHERE id='$id_session' AND code='".addslashes($course_code)."'",__FILE__,__LINE__);

if(!list($session_name,$course_title)=mysql_fetch_row($result))
{
	header('Location: session_course_list.php?id_session='.$id_session);
	exit();
}

if($action == 'delete')
{
	$idChecked = $_POST['idChecked'];
	if(is_array($idChecked))
	{
		$idChecked=implode(',',$idChecked);

		api_sql_query("DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='".addslashes($course_code)."' AND id_user IN($idChecked)",__FILE__,__LINE__);

		$nbr_affected_rows=mysql_affected_rows();

		api_sql_query("UPDATE $tbl_session_rel_course SET nbr_users=nbr_users-$nbr_affected_rows WHERE id_session='$id_session' AND course_code='".addslashes($course_code)."'",__FILE__,__LINE__);
	}

}

$limit=20;
$from=$page * $limit;

$result=api_sql_query("SELECT user_id,lastname,firstname,username FROM $tbl_session_rel_course_rel_user,$tbl_user WHERE user_id=id_user AND id_session='$id_session' AND course_code='".addslashes($course_code)."' ORDER BY $sort LIMIT $from,".($limit+1),__FILE__,__LINE__);

$Users=api_store_result($result);

$nbr_results=sizeof($Sessions);

$tool_name = "Liste des utilisateurs inscrits au cours &quot;".htmlentities($course_title)."&quot; pour la session &quot;".htmlentities($session_name)."&quot;";

$interbreadcrumb[]=array("url" => "index.php","name" => get_lang('AdministrationTools'));
$interbreadcrumb[]=array("url" => "session_list.php","name" => "Liste des sessions");
$interbreadcrumb[]=array("url" => "session_course_list.php?id_session=$id_session","name" => "Liste des cours de la session &quot;".htmlentities($session_name)."&quot;");

Display::display_header($tool_name);

api_display_tool_title($tool_name);

$tableHeader = array();
$tableHeader[] = array(' ');
$tableHeader[] = array(get_lang('LastName'));
$tableHeader[] = array(get_lang('FirstName'));
$tableHeader[] = array(get_lang('LoginName'));
$tableHeader[] = array(get_lang('Actions'));

?>

<div id="main">


<?php
$tableUsers = array();

foreach($Users as $key=>$enreg)
{
	if($key == $limit)
	{
		break;
	}
	$user = array();
	$user[] = '<input type="checkbox" name="idChecked[]" value="'.$enreg['user_id'].'">';
	$user[] = htmlentities($enreg['lastname']);
	$user[] = htmlentities($enreg['firstname']);
	$user[] = htmlentities($enreg['username']);
	$user[] = '<a href="'.$PHP_SELF.'?id_session='.$id_session.'&course_code='.urlencode($course_code).'&sort='.$sort.'&action=delete&idChecked[]='.$enreg['user_id'].'" onclick="javascript:if(!confirm(\''.get_lang('Confirm').'\')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" title="'.get_lang('Delete').'"></a>';
	$tableUsers[] = $user;
}
echo '<form method="post" action="'.$PHP_SELF.'">';
Display :: display_sortable_table($tableHeader, $tableUsers, array (), array ());

echo '
<select name="action">
<option value="delete">'.get_lang('UnsubscribeUsersFromCourse').'</option>
</select>
<input type="submit" value="'.get_lang('Ok').'"></form>';


echo '</div>';

Display::display_footer();
?>