<?php // $Id: course_category.php 18668 2009-02-24 19:12:46Z cvargas1 $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	@package dokeos.admin
==============================================================================
*/

// name of the language file that needs to be included 
$language_file='admin';

$cidReset=true;

require('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$category=$_GET['category'];
$action=$_GET['action'];

$tbl_course  = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_category = Database::get_main_table(TABLE_MAIN_CATEGORY);

$errorMsg='';

if(!empty($action))
{
	if($action == 'delete')
	{
		deleteNode($_GET['id']);

		header('Location: '.api_get_self().'?category='.Security::remove_XSS($category));
		exit();
	}
	elseif(($action == 'add' || $action == 'edit') && $_POST['formSent'])
	{
		$_POST['categoryCode']=trim($_POST['categoryCode']);
		$_POST['categoryName']=trim($_POST['categoryName']);

		if(!empty($_POST['categoryCode']) && !empty($_POST['categoryName']))
		{
			if($action == 'add')
			{
				$ret=addNode($_POST['categoryCode'],$_POST['categoryName'],$_POST['canHaveCourses'],$category);
			}
			else
			{
				$ret=editNode($_POST['categoryCode'],$_POST['categoryName'],$_POST['canHaveCourses'],$_GET['id']);
			}

			if($ret)
			{
				$action='';
			}
			else
			{
				$errorMsg=get_lang('CatCodeAlreadyUsed');
			}
		}
		else
		{
			$errorMsg=get_lang('PleaseEnterCategoryInfo');
		}
	}
	elseif($action == 'edit')
	{
		$categoryCode=Database::escape_string($_GET['id']);

		$result=api_sql_query("SELECT name,auth_course_child FROM $tbl_category WHERE code='$categoryCode'",__FILE__,__LINE__);

		list($categoryName,$canHaveCourses)=Database::fetch_row($result);

		$canHaveCourses=($canHaveCourses == 'FALSE')?0:1;
	}
	elseif($action == 'moveUp')
	{
		moveNodeUp($_GET['id'],$_GET['tree_pos'],$category);

		header('Location: '.api_get_self().'?category='.Security::remove_XSS($category));
		exit();
	}
}

$tool_name=get_lang('AdminCategories');

$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => 'configure_homepage.php',"name" => get_lang('ConfigureHomePage'));

Display::display_header($tool_name);

//api_display_tool_title($tool_name);

if(!empty($category))
{
	$myquery = "SELECT * FROM $tbl_category WHERE code ='$category'";
	$result	= api_sql_query($myquery,__FILE__,__LINE__);
	if(Database::num_rows($result)==0)
	{
		$category = '';
	}
}

if(empty($action))
{
	$myquery="SELECT t1.name,t1.code,t1.parent_id,t1.tree_pos,t1.children_count,COUNT(DISTINCT t3.code) AS nbr_courses FROM $tbl_category t1 LEFT JOIN $tbl_category t2 ON t1.code=t2.parent_id LEFT JOIN $tbl_course t3 ON t3.category_code=t1.code WHERE t1.parent_id ".(empty($category)?"IS NULL":"='$category'")." GROUP BY t1.name,t1.code,t1.parent_id,t1.tree_pos,t1.children_count ORDER BY t1.tree_pos";
	$result=api_sql_query($myquery,__FILE__,__LINE__);

	$Categories=api_store_result($result);
}

	
	

if($action == 'add' || $action == 'edit')
{
	?>
	<div class="actions">
	<a href="<?php echo api_get_self(); ?>?category=<?php echo Security::remove_XSS($category); ?>"><?php echo Display::return_icon('folder_up.gif').get_lang("Back"); if(!empty($category)) echo ' ('.Security::remove_XSS($category).')'; ?></a>
	</div>	
	
	<h3><?php echo ($action == 'add')?get_lang('AddACategory'):get_lang('EditNode'); if(!empty($category)) echo ' '.get_lang('Into').' '.Security::remove_XSS($category); ?></h3>
	
	<form method="post" action="<?php echo api_get_self(); ?>?action=<?php echo Security::remove_XSS($action); ?>&category=<?php echo Security::remove_XSS($category); ?>&amp;id=<?php echo Security::remove_XSS($_GET['id']); ?>">
	<input type="hidden" name="formSent" value="1" />
	<table border="0" cellpadding="5" cellspacing="0">
	
	<?php
	if(!empty($errorMsg))
	{
	?>
	
	<tr>
	  <td colspan="2">
	
	<?php
		Display::display_normal_message($errorMsg); //main API
	?>
	
	  </td>
	</tr>
	
	<?php
	}
	?>
	
	<tr>
	  <td nowrap="nowrap"><?php echo get_lang("CategoryCode"); ?> :</td>
	  <td><input type="text" name="categoryCode" size="20" maxlength="20" value="<?php echo htmlentities(stripslashes($categoryCode),ENT_QUOTES,$charset); ?>" /></td>
	</tr>
	<tr>
	  <td nowrap="nowrap"><?php echo get_lang("CategoryName"); ?> :</td>
	  <td><input type="text" name="categoryName" size="20" maxlength="100" value="<?php echo htmlentities(stripslashes($categoryName),ENT_QUOTES,$charset); ?>" /></td>
	</tr>
	<tr>
	  <td nowrap="nowrap"><?php echo get_lang("AllowCoursesInCategory"); ?></td>
	  <td>
		<input class="checkbox" type="radio" name="canHaveCourses" value="0" <?php if(($action == 'edit' && !$canHaveCourses) || ($action == 'add' && $formSent && !$canHaveCourses)) echo 'checked="checked"'; ?> /><?php echo get_lang("No"); ?>
		<input class="checkbox" type="radio" name="canHaveCourses" value="1" <?php if(($action == 'edit' && $canHaveCourses) || ($action == 'add' && !$formSent || $canHaveCourses)) echo 'checked="checked"'; ?> /><?php echo get_lang("Yes"); ?>
	  </td>
	</tr>
	<tr>
	  <td>&nbsp;</td>
	  <td><input type="submit" value="<?php echo get_lang("Ok"); ?>" /></td>
	</tr>
	</table>
	</form>
	
	<?php
}
else
{
?>
<div class="actions">
<?php 
if(!empty($category) && empty($action))
{
	$myquery = "SELECT parent_id FROM $tbl_category WHERE code='$category'";
	$result=api_sql_query($myquery,__FILE__,__LINE__);
	$parent_id = 0;
	if(Database::num_rows($result)>0){
		$parent_id=Database::fetch_array($result);
	}
	
	$parent_id['parent_id']?$link=' ('.$parent_id['parent_id'].')':$link='';
	?>

	<a href="<?php echo api_get_self(); ?>?category=<?php echo $parent_id['parent_id']; ?>"><?php echo Display::return_icon('folder_up.gif').get_lang("Back"); if(!empty($parent_id)) echo $link ?></a>

	<?php 
}
?>
<a href="<?php echo api_get_self(); ?>?category=<?php echo Security::remove_XSS($category); ?>&amp;action=add"><?php echo Display::return_icon('folder_new.gif').get_lang("AddACategory"); if(!empty($category)) echo ' '.get_lang('Into').' '.Security::remove_XSS($category); ?></a>
</div>
<ul>

<?php
if(count($Categories)>0)
{
	foreach($Categories as $enreg)
	{
	?>
	  <li>
		<a href="<?php echo api_get_self(); ?>?category=<?php echo Security::remove_XSS($enreg['code']); ?>"><?php Display::display_icon('folder_document.gif', get_lang('OpenNode')); ?></a>
		<a href="<?php echo api_get_self(); ?>?category=<?php echo Security::remove_XSS($category); ?>&amp;action=edit&amp;id=<?php echo Security::remove_XSS($enreg['code']); ?>"><?php Display::display_icon('edit.gif', get_lang('EditNode')); ?></a>
		<a href="<?php echo api_get_self(); ?>?category=<?php echo Security::remove_XSS($category); ?>&amp;action=delete&amp;id=<?php echo Security::remove_XSS($enreg['code']); ?>" onclick="javascript:if(!confirm('<?php echo addslashes(htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)); ?>')) return false;"><?php Display::display_icon('delete.gif', get_lang('DeleteNode'));?></a>
		<a href="<?php echo api_get_self(); ?>?category=<?php echo Security::remove_XSS($category); ?>&amp;action=moveUp&amp;id=<?php echo Security::remove_XSS($enreg['code']); ?>&amp;tree_pos=<?php echo $enreg['tree_pos']; ?>"><?php Display::display_icon('up.gif', get_lang('UpInSameLevel'));?></a>
		<?php echo $enreg['name']; ?>
		(<?php echo $enreg['children_count']; ?> <?php echo get_lang("Categories"); ?> - <?php echo $enreg['nbr_courses']; ?> <?php echo get_lang("Courses"); ?>)
	  </li>
	<?php
	}
	unset($Categories);
}
else
{
	echo get_lang("NoCategories");
}
?>
</ul>



<?php
}

/*
==============================================================================
		FOOTER 
==============================================================================
*/ 
Display::display_footer();

/******** Functions ********/

function deleteNode($node)
{
	global $tbl_category, $tbl_course;
	$node = Database::escape_string($node);

	$result=api_sql_query("SELECT parent_id,tree_pos FROM $tbl_category WHERE code='$node'",__FILE__,__LINE__);

	if($row=Database::fetch_array($result))
	{
		if(!empty($row['parent_id']))
		{
			api_sql_query("UPDATE $tbl_course SET category_code='".$row['parent_id']."' WHERE category_code='$node'",__FILE__,__LINE__);
			api_sql_query("UPDATE $tbl_category SET parent_id='".$row['parent_id']."' WHERE parent_id='$node'",__FILE__,__LINE__);
		}
		else
		{
			api_sql_query("UPDATE $tbl_course SET category_code='' WHERE category_code='$node'",__FILE__,__LINE__);
			api_sql_query("UPDATE $tbl_category SET parent_id=NULL WHERE parent_id='$node'",__FILE__,__LINE__);
		}

		api_sql_query("UPDATE $tbl_category SET tree_pos=tree_pos-1 WHERE tree_pos > '".$row['tree_pos']."'",__FILE__,__LINE__);
		api_sql_query("DELETE FROM $tbl_category WHERE code='$node'",__FILE__,__LINE__);

		if(!empty($row['parent_id']))
		{
			updateFils($row['parent_id']);
		}
	}
}

function addNode($code,$name,$canHaveCourses,$parent_id)
{
	global $tbl_category;

	$canHaveCourses=$canHaveCourses?'TRUE':'FALSE';
	$code 			= Database::escape_string($code);
	$name 			= Database::escape_string($name);
	$parent_id		= Database::escape_string($parent_id);	

	$result=api_sql_query("SELECT 1 FROM $tbl_category WHERE code='$code'",__FILE__,__LINE__);

	if(Database::num_rows($result))
	{
		return false;
	}

	$result=api_sql_query("SELECT MAX(tree_pos) AS maxTreePos FROM $tbl_category",__FILE__,__LINE__);

	$row=Database::fetch_array($result);

	$tree_pos=$row['maxTreePos']+1;

	api_sql_query("INSERT INTO $tbl_category(name,code,parent_id,tree_pos,children_count,auth_course_child) VALUES('$name','$code',".(empty($parent_id)?"NULL":"'$parent_id'").",'$tree_pos','0','$canHaveCourses')",__FILE__,__LINE__);

	updateFils($parent_id);

	return true;
}

function editNode($code,$name,$canHaveCourses,$old_code)
{
	global $tbl_category;

	$canHaveCourses=$canHaveCourses?'TRUE':'FALSE';
	$code 			= Database::escape_string($code);
	$name 			= Database::escape_string($name);
	$old_code 		= Database::escape_string($old_code);

	if($code != $old_code)
	{
		$result=api_sql_query("SELECT 1 FROM $tbl_category WHERE code='$code'",__FILE__,__LINE__);

		if(Database::num_rows($result))
		{
			return false;
		}
	}

	api_sql_query("UPDATE $tbl_category SET name='$name',code='$code',auth_course_child='$canHaveCourses' WHERE code='$old_code'",__FILE__,__LINE__);

	return true;
}

function moveNodeUp($code,$tree_pos,$parent_id)
{
	global $tbl_category;
	$code 		= Database::escape_string($code);
	$tree_pos 	= Database::escape_string($tree_pos);
	$parent_id	= Database::escape_string($parent_id);
	
	$result=api_sql_query("SELECT code,tree_pos FROM $tbl_category WHERE parent_id ".(empty($parent_id)?"IS NULL":"='$parent_id'")." AND tree_pos<'$tree_pos' ORDER BY tree_pos DESC LIMIT 0,1",__FILE__,__LINE__);

	if(!$row=Database::fetch_array($result))
	{
		$result=api_sql_query("SELECT code,tree_pos FROM $tbl_category WHERE parent_id ".(empty($parent_id)?"IS NULL":"='$parent_id'")." AND tree_pos>'$tree_pos' ORDER BY tree_pos DESC LIMIT 0,1",__FILE__,__LINE__);

		if(!$row=Database::fetch_array($result))
		{
			return false;
		}
	}

	api_sql_query("UPDATE $tbl_category SET tree_pos='".$row['tree_pos']."' WHERE code='$code'",__FILE__,__LINE__);
	api_sql_query("UPDATE $tbl_category SET tree_pos='$tree_pos' WHERE code='$row[code]'",__FILE__,__LINE__);
}

function updateFils($category)
{
	global $tbl_category;
	$category = Database::escape_string($category);
	$result=api_sql_query("SELECT parent_id FROM $tbl_category WHERE code='$category'",__FILE__,__LINE__);

	if($row=Database::fetch_array($result))
	{
		updateFils($row['parent_id']);
	}

	$children_count=compterFils($category,0)-1;

	api_sql_query("UPDATE $tbl_category SET children_count='$children_count' WHERE code='$category'",__FILE__,__LINE__);
}

function compterFils($pere,$cpt)
{
	global $tbl_category;
	$pere = Database::escape_string($pere);
	$result=api_sql_query("SELECT code FROM $tbl_category WHERE parent_id='$pere'",__FILE__,__LINE__);

	while($row=Database::fetch_array($result))
	{
		$cpt=compterFils($row['code'],$cpt);
	}

	return ($cpt+1);
}
?>