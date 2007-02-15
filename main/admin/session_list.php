<?php
// name of the language file that needs to be included
$language_file='admin';

$cidReset=true;

include('../inc/global.inc.php');

// setting the section (for the tabs)
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

// Database Table Definitions
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$page=intval($_GET['page']);
$action=$_REQUEST['action'];
$sort=in_array($_GET['sort'],array('name','nbr_courses','date_start','date_end'))?$_GET['sort']:'name';

if($action == 'delete')
{
	$idChecked = $_REQUEST['idChecked'];
	if(is_array($idChecked))
	{
		$idChecked=implode(',',$idChecked);
	}
	else
	{
		$idChecked=intval($idChecked);
	}

	api_sql_query("DELETE FROM $tbl_session WHERE id IN($idChecked)",__FILE__,__LINE__);
	
	api_sql_query("DELETE FROM $tbl_session_rel_course WHERE id_session IN($idChecked)",__FILE__,__LINE__);

	api_sql_query("DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session IN($idChecked)",__FILE__,__LINE__);

	//header('Location: '.$PHP_SELF.'?sort='.$sort);
	//exit();
}

$limit=20;
$from=$page * $limit;

$result=api_sql_query("SELECT id,name,nbr_courses,date_start,date_end FROM $tbl_session ".(empty($_POST['keyword']) ? "" : "WHERE name LIKE '%".addslashes($_POST['keyword'])."%'")." ORDER BY $sort LIMIT $from,".($limit+1),__FILE__,__LINE__);

$Sessions=api_store_result($result);

$nbr_results=sizeof($Sessions);

$tool_name = get_lang('ListSession');

$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));

Display::display_header($tool_name);

api_display_tool_title($tool_name);
?>

<div id="main">

<?php

if(isset($_GET['action'])){
	if($_GET['action'] == 'delete'){
		Display::display_normal_message(get_lang('SessionDeleted'));
	}
	else{
		Display::display_normal_message(stripslashes($_GET['message']));
	}
}

?>
<form method="POST" action="session_list.php">
		<input type="text" name="keyword" value="<?php echo $_GET['keyword']; ?>"/>
	<input type="submit" value="<?php echo get_lang('Search'); ?>"/>
	</form>
<form method="post" action="<?php echo $PHP_SELF; ?>?sort=<?php echo $sort; ?>" onsubmit="javascript:if(!confirm('Veuillez confirmer votre choix.')) return false;">

<div align="left">

<?php

if(count($Sessions)==0 && isset($_POST['keyword']))
{
	echo get_lang('NoSearchResults');
}
else 
{
	
	
	$table_header[] = array (' ', false);
	$table_header[] = array (get_lang('SessionName'), true);
	$table_header[] = array (get_lang('NbCourses'), true);
	$table_header[] = array (get_lang('DateStart'), true);
	$table_header[] = array (get_lang('DateEnd'), true);
	$table_header[] = array ('Actions', false);
		
	$i=0;
	$sessions = array();
	foreach($Sessions as $key=>$enreg)
	{
		if($key == $limit)
		{
			break;
		}
		$session = array();
		$session[] = '<input type="checkbox" name="idChecked[]" value="'.$enreg['id'].'">';
		$session[] = '<a href="resume_session.php?id_session='.$enreg['id'].'">'.htmlentities($enreg['name']).'</a>';
		$session[] = '<a href="session_course_list.php?id_session='.$enreg['id'].'">'.htmlentities($enreg['nbr_courses']).' cours</a>';
		$session[] = htmlentities($enreg['date_start']);
		$session[] = htmlentities($enreg['date_end']);
		$session[] = '<a href="add_users_to_session.php?page=session_list.php&id_session='.$enreg['id'].'"><img src="../img/add_multiple_users.gif" border="0" align="absmiddle" title="'.get_lang('SubscribeUsersToSession').'"></a>
					<a href="add_courses_to_session.php?page=session_list.php&id_session='.$enreg['id'].'"><img src="../img/synthese_view.gif" border="0" align="absmiddle" title="'.get_lang('SubscribeCoursesToSession').'"></a>
					<a href="session_edit.php?page=session_list.php&id='.$enreg['id'].'"><img src="../img/edit.gif" border="0" align="absmiddle" title="'.get_lang('Edit').'"></a>
					<a href="'.$PHP_SELF.'?sort='.$sort.'&action=delete&idChecked='.$enreg['id'].'" onclick="javascript:if(!confirm(\''.get_lang('Confirm').'\')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" title="'.get_lang('Delete').'"></a>';
		$sessions[] = $session;

		$i=$i ? 0 : 1;
	}
	
	unset($Sessions);

	echo '<form method="post" action="'.$PHP_SELF.'">';
	Display :: display_sortable_table($table_header, $sessions, array (), array (), $parameters);
	echo '<select name="action">
			<option value="delete">'.get_lang('DeleteSelectedSessions').'</option>
			</select>
			<input type="submit" value="'.get_lang('Ok').'">
			</form>';
}
	?>
	
</div>

<br>


</table>

</div>

<?php

Display::display_footer();
?>