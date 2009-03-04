<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
==============================================================================
*/

$language_file='admin';
$cidReset=true;

include('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'sessionmanager.lib.php');

api_protect_admin_script(true);

$tbl_session=Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course=Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_course_rel_user=Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session_rel_user=Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);

$page=intval($_GET['page']);
$action=$_REQUEST['action'];
$sort=in_array($_GET['sort'],array('name','nbr_courses','date_start','date_end'))?$_GET['sort']:'name';
$idChecked = $_REQUEST['idChecked'];

if ($action == 'delete') {
	SessionManager::DeleteSession($idChecked);
	header('Location: '.api_get_self().'?sort='.$sort);
	exit();
}

$interbreadcrumb[]=array("url" => "index.php","name" => get_lang('PlatformAdmin'));

if (isset ($_GET['search']) && $_GET['search'] == 'advanced') {
	
	$interbreadcrumb[] = array ("url" => 'session_list.php', "name" => get_lang('SessionList'));
	$tool_name = get_lang('SearchASession');
	Display :: display_header($tool_name);
	
	$form = new FormValidator('advanced_search','get');
	
	$active_group = array();
	$active_group[] = $form->createElement('checkbox','active','',get_lang('Active'));
	$active_group[] = $form->createElement('checkbox','inactive','',get_lang('Inactive'));
	$form->addGroup($active_group,'',get_lang('ActiveSession'),'<br/>',false);
	
	$form->addElement('submit','submit',get_lang('Ok'));
	$defaults['active'] = 1;
	$defaults['inactive'] = 1;
	$form->setDefaults($defaults);
	$form->display();
	
} else {
	
	$limit=20;
	$from=$page * $limit;
	
	//if user is crfp admin only list its sessions
	if(!api_is_platform_admin()) {
		$where = 'WHERE session_admin_id='.intval($_user['user_id']);
		$where .= (empty($_REQUEST['keyword']) ? " " : " AND name LIKE '%".addslashes($_REQUEST['keyword'])."%'");
	}
	else {
		$where .= (empty($_REQUEST['keyword']) ? " " : " WHERE name LIKE '%".addslashes($_REQUEST['keyword'])."%'");
	}
	
	if(trim($where) == ''){
		$and=" WHERE id_coach=user_id";
	} else {
		$and=" AND id_coach=user_id";
	}
	
	if (isset($_REQUEST['active']) && !isset($_REQUEST['inactive']) ){
		$and .= ' AND ( (session.date_start <= CURDATE() AND session.date_end >= CURDATE()) OR session.date_start="0000-00-00" ) ';
	}
	if (!isset($_REQUEST['active']) && isset($_REQUEST['inactive']) ){
		$and .= ' AND ( (session.date_start > CURDATE() OR session.date_end < CURDATE()) AND session.date_start<>"0000-00-00" ) ';
	}
	
	$query= "SELECT id,name,nbr_courses,date_start,date_end, firstname, lastname 
			FROM $tbl_session, $tbl_user
			$where
			$and
			ORDER BY $sort 
			LIMIT $from,".($limit+1);
	//filtering the session list by access_url
	if ($_configuration['multiple_access_urls']==true){
		$table_access_url_rel_session= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);	
		$access_url_id = api_get_current_access_url_id();
		if ($access_url_id != -1) {				
			$and.= " AND access_url_id = $access_url_id AND $table_access_url_rel_session.session_id = $tbl_session.id";
			$query= "SELECT id,name,nbr_courses,date_start,date_end, firstname, lastname 
				FROM $tbl_session, $tbl_user, $table_access_url_rel_session
				$where
				$and
				ORDER BY $sort 
				LIMIT $from,".($limit+1);
				
		}	
	}
			
	$result=api_sql_query($query,__FILE__,__LINE__);	
	$Sessions=api_store_result($result);	
	$nbr_results=sizeof($Sessions);	
	$tool_name = get_lang('SessionList');	
	Display::display_header($tool_name);	
	api_display_tool_title($tool_name);
    
    if (!empty($_GET['warn'])) {
        Display::display_warning_message(urldecode($_GET['warn']));
    }
    if(isset($_GET['action'])) {
        Display::display_normal_message($_GET['message']);
    }
    ?>	
	<div id="main">
	<div class="actions">		
	<?php
		
	echo '<div style="float:right;">
		<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_add.php">'.Display::return_icon('view_more_stats.gif',get_lang('AddSession')).get_lang('AddSession').'</a>									
	 </div>';	  
	?>  
	<form method="POST" action="session_list.php">
		<input type="text" name="keyword" value="<?php echo Security::remove_XSS($_GET['keyword']); ?>"/>
		<input type="submit" value="<?php echo get_lang('Search'); ?>"/>
		<a href="session_list.php?search=advanced"><?php echo get_lang('AdvancedSearch'); ?></a>
		</form>
	<form method="post" action="<?php echo api_get_self(); ?>?action=delete&sort=<?php echo $sort; ?>" onsubmit="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">	
	<div align="left">	
	<?php	
	if(count($Sessions)==0 && isset($_POST['keyword'])) {
		echo get_lang('NoSearchResults');
	} else {
		if($page) {
		?>	
		<a href="<?php echo api_get_self(); ?>?page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>&keyword=<?php echo $_REQUEST['keyword']; ?>"><?php echo get_lang('Previous'); ?></a>	
		<?php
		} else {
			echo get_lang('Previous');
		}
		?>	
		|	
		<?php
		if($nbr_results > $limit) {
			?>	
			<a href="<?php echo api_get_self(); ?>?page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>&keyword=<?php echo $_REQUEST['keyword']; ?>"><?php echo get_lang('Next'); ?></a>	
			<?php
		} else {
			echo get_lang('Next');
		}
		?>	
	</div>	
	 </div>
	
		<br>
	
		<table class="data_table" width="100%">
		<tr>
		  <th>&nbsp;</th>
		  <th><a href="<?php echo api_get_self(); ?>?sort=name"><?php echo get_lang('NameOfTheSession'); ?></a></th>
		  <th><a href="<?php echo api_get_self(); ?>?sort=nbr_courses"><?php echo get_lang('NumberOfCourses'); ?></a></th>
		  <th><a href="<?php echo api_get_self(); ?>?sort=date_start"><?php echo get_lang('StartDate'); ?></a></th>
		  <th><a href="<?php echo api_get_self(); ?>?sort=date_end"><?php echo get_lang('EndDate'); ?></a></th>
		  <th><a href="<?php echo api_get_self(); ?>?sort=coach_name"><?php echo get_lang('Coach'); ?></a></th>
		  <th><?php echo get_lang('Actions'); ?></th>
		</tr>
	
		<?php
		$i=0;
	
		foreach ($Sessions as $key=>$enreg) {
			if($key == $limit) {
				break;
			}
			$sql = 'SELECT COUNT(course_code) FROM '.$tbl_session_rel_course.' WHERE id_session='.intval($enreg['id']);
	
		  	$rs = api_sql_query($sql, __FILE__, __LINE__);
		  	list($nb_courses) = Database::fetch_array($rs);
	
		?>
	
		<tr class="<?php echo $i?'row_odd':'row_even'; ?>">
		  <td><input type="checkbox" name="idChecked[]" value="<?php echo $enreg['id']; ?>"></td>
		  <td><a href="resume_session.php?id_session=<?php echo $enreg['id']; ?>"><?php echo htmlentities($enreg['name'],ENT_QUOTES,$charset); ?></a></td>
		  <td><a href="session_course_list.php?id_session=<?php echo $enreg['id']; ?>"><?php echo $nb_courses; ?> cours</a></td>
		  <td><?php echo htmlentities($enreg['date_start'],ENT_QUOTES,$charset); ?></td>
		  <td><?php echo htmlentities($enreg['date_end'],ENT_QUOTES,$charset); ?></td>
		  <td><?php echo htmlentities($enreg['firstname'],ENT_QUOTES,$charset).' '.htmlentities($enreg['lastname'],ENT_QUOTES,$charset); ?></td>
		  <td>
			<a href="add_users_to_session.php?page=session_list.php&id_session=<?php echo $enreg['id']; ?>"><?php Display::display_icon('add_user_big.gif', get_lang('SubscribeUsersToSession')); ?></a>
			<a href="add_courses_to_session.php?page=session_list.php&id_session=<?php echo $enreg['id']; ?>"><?php Display::display_icon('synthese_view.gif', get_lang('SubscribeCoursesToSession')); ?></a>
			<a href="session_edit.php?page=session_list.php&id=<?php echo $enreg['id']; ?>"><?php Display::display_icon('edit.gif', get_lang('Edit')); ?></a>
			<a href="<?php echo api_get_self(); ?>?sort=<?php echo $sort; ?>&action=delete&idChecked=<?php echo $enreg['id']; ?>" onclick="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;"><?php Display::display_icon('delete.gif', get_lang('Delete')); ?></a>
		  </td>
		</tr>
	
		<?php
			$i=$i ? 0 : 1;
		}
	
		unset($Sessions);
	
		?>
	
		</table>
	
		<br>
	
		<div align="left">
	
		<?php
		if($page)
		{
		?>
	
		<a href="<?php echo api_get_self(); ?>?page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>&keyword=<?php echo $_REQUEST['keyword']; ?>"><?php echo get_lang('Previous'); ?></a>
	
		<?php
		}
		else
		{
			echo get_lang('Previous');
		}
		?>
	
		|
	
		<?php
		if($nbr_results > $limit)
		{
		?>
	
		<a href="<?php echo api_get_self(); ?>?page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>&keyword=<?php echo $_REQUEST['keyword']; ?>"><?php echo get_lang('Next'); ?></a>
	
		<?php
		}
		else
		{
			echo get_lang('Next');
		}
		?>
	
		</div>
	
		<br>
	
		<select name="action">
		<option value="delete"><?php echo get_lang('DeleteSelectedSessions'); ?></option>
		</select>
		<input type="submit" value="<?php echo get_lang('Ok'); ?>">
		<?php } ?>
	</table>
	
	</div>

<?php

}

Display::display_footer();
?>