<?php
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	@package dokeos.admin
==============================================================================
*/

$language_file='admin';
$cidReset=true;
include('../inc/global.inc.php');
api_protect_admin_script(true);
$tbl_user=Database::get_main_table(TABLE_MAIN_USER);
$tbl_course=Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_session=Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course=Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_course_rel_user=Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$id_session=intval($_GET['id_session']);
$course_code=trim(stripslashes($_GET['course_code']));
$page=intval($_GET['page']);
$action=$_REQUEST['action'];
$sort=in_array($_GET['sort'],array('lastname','firstname','username'))?$_GET['sort']:api_sort_by_first_name()?'firstname':'lastname';
$idChecked = (is_array($_GET['idChecked']) ? $_GET['idChecked'] : (is_array($_POST['idChecked']) ? $_POST['idChecked'] : null));
if (is_array($idChecked)) {
	$my_temp = array();
	foreach ($idChecked as $id){
		$my_temp[]= intval($id);// forcing the intval
	}
	$idChecked = $my_temp;
}

$sql = "SELECT s.name, c.title  FROM $tbl_session_rel_course src 
		INNER JOIN $tbl_session s ON s.id = src.id_session
		INNER JOIN $tbl_course c ON c.code = src.course_code
		WHERE src.id_session='$id_session' AND src.course_code='".Database::escape_string($course_code)."' "; 

$result=Database::query($sql);
if(!list($session_name,$course_title)=Database::fetch_row($result))
{
	header('Location: session_course_list.php?id_session='.$id_session);
	exit();
}

if($action == 'delete') {
	if(is_array($idChecked) && count($idChecked)>0 ) {
		$idChecked=implode(',',$idChecked);
		Database::query("DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='".addslashes($course_code)."' AND id_user IN($idChecked)");
		$nbr_affected_rows=Database::affected_rows();
		Database::query("UPDATE $tbl_session_rel_course SET nbr_users=nbr_users-$nbr_affected_rows WHERE id_session='$id_session' AND course_code='".addslashes($course_code)."'");
	}
	header('Location: '.api_get_self().'?id_session='.$id_session.'&course_code='.urlencode($course_code).'&sort='.$sort);
	exit();
}

$limit=20;
$from=$page * $limit;
$is_western_name_order = api_is_western_name_order();

$result=Database::query("SELECT u.user_id,".($is_western_name_order ? 'u.firstname, u.lastname' : 'u.lastname, u.firstname').", u.username FROM $tbl_session_rel_course_rel_user scru, $tbl_user u WHERE u.user_id=scru.id_user AND scru.id_session='$id_session' AND scru.status<>2 AND scru.course_code='".addslashes($course_code)."' ORDER BY $sort LIMIT $from,".($limit+1));
$Users=Database::store_result($result);

$nbr_results=sizeof($Users);

$tool_name = get_lang('ListOfUsersSubscribedToCourse').' &quot;'.api_htmlentities($course_title,ENT_QUOTES,$charset).'&quot; '.get_lang('ForTheSession').' &quot;'.api_htmlentities($session_name,ENT_QUOTES,$charset).'&quot;';

$interbreadcrumb[]=array("url" => "index.php","name" => get_lang('AdministrationTools'));
$interbreadcrumb[]=array("url" => "session_list.php","name" => get_lang('SessionList'));
$interbreadcrumb[]=array("url" => "session_course_list.php?id_session=$id_session","name" => get_lang('ListOfCoursesOfSession')." &quot;".api_htmlentities($session_name,ENT_QUOTES,$charset)."&quot;");

Display::display_header($tool_name);
api_display_tool_title($tool_name);
?>
<form method="post" action="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=<?php echo $sort; ?>" onsubmit="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">
<div align="right">
<?php
if($page) {
?>
<a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Previous');?></a>
<?php
} else {
	echo get_lang('Previous');
}
?>
|
<?php
if($nbr_results > $limit) {
?>
<a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Next');?></a>
<?php
} else {
	echo get_lang('Next');
}
?>

</div>
<br />

<table class="data_table" width="100%">
<tr>
  <th>&nbsp;</th>
  <?php if ($is_western_name_order) { ?>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=firstname"><?php echo get_lang('FirstName');?></a></th>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=lastname"><?php echo get_lang('LastName');?></a></th>
  <?php } else { ?>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=lastname"><?php echo get_lang('LastName');?></a></th>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=firstname"><?php echo get_lang('FirstName');?></a></th>
  <?php } ?>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=username"><?php echo get_lang('Login');?></a></th>
  <th><?php echo get_lang('Actions');?></th>
</tr>

<?php
$i=0;

foreach($Users as $key=>$enreg) {
	if($key == $limit) {
		break;
	}
?>

<tr class="<?php echo $i?'row_odd':'row_even'; ?>">
  <td><input type="checkbox" name="idChecked[]" value="<?php echo $enreg['user_id']; ?>"></td>
  <?php if ($is_western_name_order) { ?>
  <td><?php echo api_htmlentities($enreg['firstname'],ENT_QUOTES,$charset); ?></td>
  <td><?php echo api_htmlentities($enreg['lastname'],ENT_QUOTES,$charset); ?></td>
  <?php } else { ?>
  <td><?php echo api_htmlentities($enreg['lastname'],ENT_QUOTES,$charset); ?></td>
  <td><?php echo api_htmlentities($enreg['firstname'],ENT_QUOTES,$charset); ?></td>
  <?php } ?>
  <td><?php echo api_htmlentities($enreg['username'],ENT_QUOTES,$charset); ?></td>
  <td>
	<a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=<?php echo $sort; ?>&action=delete&idChecked[]=<?php echo $enreg['user_id']; ?>" onclick="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;"><?php Display::display_icon('delete.gif', get_lang('Delete')); ?></a>
  </td>
</tr>

<?php
	$i=$i ? 0 : 1;
}

unset($Users);
?>

</table>
<br />
<div align="left">
<?php
if($page) {
?>
<a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Previous'); ?></a>
<?php
} else {
	echo get_lang('Previous');
}
?>
|
<?php
if($nbr_results > $limit) {
?>
<a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Next'); ?></a>
<?php
} else {
	echo get_lang('Next');
}
?>
</div>
<br />
<select name="action">
<option value="delete"><?php echo get_lang('UnsubscribeSelectedUsersFromSession');?></option>
</select>
<button class="save" type="submit"> <?php echo get_lang('Ok'); ?></button>
</form>
<?php
Display::display_footer();