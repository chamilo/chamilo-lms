<?php
$language_file='admin';

$cidReset=true;

include('../inc/global.inc.php');

api_protect_admin_script();

$tbl_session=Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course=Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_course_rel_user=Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$page=intval($_GET['page']);
$action=$_REQUEST['action'];
$sort=in_array($_GET['sort'],array('name','nbr_courses','date_start','date_end'))?$_GET['sort']:'name';
$idChecked = $_REQUEST['idChecked'];


if($action == 'delete')
{
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

	header('Location: '.$PHP_SELF.'?sort='.$sort);
	exit();
}

$limit=20;
$from=$page * $limit;

$result=api_sql_query("SELECT id,name,nbr_courses,date_start,date_end FROM $tbl_session ".(empty($_POST['keyword']) ? "" : "WHERE name LIKE '%".addslashes($_POST['keyword'])."%'")." ORDER BY $sort LIMIT $from,".($limit+1),__FILE__,__LINE__);

$Sessions=api_store_result($result);

$nbr_results=sizeof($Sessions);

$tool_name = "Liste des sessions";

$interbreadcrumb[]=array("url" => "index.php","name" => get_lang('AdministrationTools'));

Display::display_header($tool_name);

api_display_tool_title($tool_name);
?>

<div id="main">

<?php

if(isset($_GET['action'])){
	Display::display_normal_message(stripslashes($_GET['message']), false);
}

?>
<form method="POST" action="session_list.php">
		<input type="text" name="keyword" value="<?php echo $_GET['keyword']; ?>"/>
	<input type="submit" value="<?php echo get_lang('Search'); ?>"/>
	</form>
<form method="post" action="<?php echo $PHP_SELF; ?>?action=delete&sort=<?php echo $sort; ?>" onsubmit="javascript:if(!confirm('Veuillez confirmer votre choix.')) return false;">

<div align="left">

<?php

if(count($Sessions)==0 && isset($_POST['keyword']))
{
	echo get_lang('NoSearchResults');
}
else 
{
	if($page)
	{
	?>
	
	<a href="<?php echo $PHP_SELF; ?>?page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>">Précédent</a>
	
	<?php
	}
	else
	{
	?>
	
	Précédent
	
	<?php
	}
	?>
	
	|
	
	<?php
	if($nbr_results > $limit)
	{
	?>
	
	<a href="<?php echo $PHP_SELF; ?>?page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>">Suivant</a>
	
	<?php
	}
	else
	{
	?>
	
	Suivant
	
	<?php
	}
	?>
	
	</div>
	
	<br>
	
	<table class="data_table" width="100%">
	<tr>
	  <th>&nbsp;</th>
	  <th><a href="<?php echo $PHP_SELF; ?>?sort=name">Nom de la session</a></th>
	  <th><a href="<?php echo $PHP_SELF; ?>?sort=nbr_courses">Nombre de cours</a></th>
	  <th><a href="<?php echo $PHP_SELF; ?>?sort=date_start">Date de début</a></th>
	  <th><a href="<?php echo $PHP_SELF; ?>?sort=date_end">Date de fin</a></th>
	  <th>Actions</th>
	</tr>
	
	<?php
	$i=0;
	
	foreach($Sessions as $key=>$enreg)
	{
		if($key == $limit)
		{
			break;
		}
		$sql = 'SELECT COUNT(course_code) FROM '.$tbl_session_rel_course.' WHERE id_session='.intval($enreg['id']);
	  	
	  	$rs = api_sql_query($sql, __FILE__, __LINE__);
	  	list($nb_courses) = mysql_fetch_array($rs);
		
	?>
	
	<tr class="<?php echo $i?'row_odd':'row_even'; ?>">
	  <td><input type="checkbox" name="idChecked[]" value="<?php echo $enreg['id']; ?>"></td>
	  <td><a href="resume_session.php?id_session=<?php echo $enreg['id']; ?>"><?php echo htmlentities($enreg['name']); ?></a></td>
	  <td><a href="session_course_list.php?id_session=<?php echo $enreg['id']; ?>"><?php echo $nb_courses; ?> cours</a></td>
	  <td><?php echo htmlentities($enreg['date_start']); ?></td>
	  <td><?php echo htmlentities($enreg['date_end']); ?></td>
	  <td>
		<a href="add_users_to_session.php?page=session_list.php&id_session=<?php echo $enreg['id']; ?>"><img src="../img/add_user_big.gif" border="0" align="absmiddle" title="Inscrire des utilisateurs à cette session"></a>
		<a href="add_courses_to_session.php?page=session_list.php&id_session=<?php echo $enreg['id']; ?>"><img src="../img/synthese_view.gif" border="0" align="absmiddle" title="Inscrire des cours à cette session"></a>
		<a href="session_edit.php?page=session_list.php&id=<?php echo $enreg['id']; ?>"><img src="../img/edit.gif" border="0" align="absmiddle" title="Editer"></a>
		<a href="<?php echo $PHP_SELF; ?>?sort=<?php echo $sort; ?>&action=delete&idChecked=<?php echo $enreg['id']; ?>" onclick="javascript:if(!confirm('Veuillez confirmer votre choix.')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" title="Effacer"></a>
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
	
	<a href="<?php echo $PHP_SELF; ?>?page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>">Précédent</a>
	
	<?php
	}
	else
	{
	?>
	
	Précédent
	
	<?php
	}
	?>
	
	|
	
	<?php
	if($nbr_results > $limit)
	{
	?>
	
	<a href="<?php echo $PHP_SELF; ?>?page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>">Suivant</a>
	
	<?php
	}
	else
	{
	?>
	
	Suivant
	
	<?php
	}
	?>
	
	</div>
	
	<br>
	
	<select name="action">
	<option value="delete">Supprimer les sessions sélectionnées</option>
	</select>
	<input type="submit" value="<?php echo get_lang('Ok'); ?>">
	<?php } ?>
</table>

</div>

<?php

Display::display_footer();
?>