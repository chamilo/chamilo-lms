<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
* 	@todo use formvalidator for the form
*/
/**
 * Code
 */
// name of the language file that needs to be included
$language_file='admin';

$cidReset=true;

require('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

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

		$result=Database::query("SELECT name,auth_course_child FROM $tbl_category WHERE code='$categoryCode'");

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
//$interbreadcrumb[]=array('url' => 'configure_homepage.php',"name" => get_lang('ConfigureHomePage'));

Display::display_header($tool_name);

//api_display_tool_title($tool_name);

if(!empty($category))
{
	$myquery = "SELECT * FROM $tbl_category WHERE code ='$category'";
	$result	= Database::query($myquery);
	if(Database::num_rows($result)==0)
	{
		$category = '';
	}
}

if(empty($action))
{
	$myquery="SELECT t1.name,t1.code,t1.parent_id,t1.tree_pos,t1.children_count,COUNT(DISTINCT t3.code) AS nbr_courses FROM $tbl_category t1 LEFT JOIN $tbl_category t2 ON t1.code=t2.parent_id LEFT JOIN $tbl_course t3 ON t3.category_code=t1.code WHERE t1.parent_id ".(empty($category)?"IS NULL":"='$category'")." GROUP BY t1.name,t1.code,t1.parent_id,t1.tree_pos,t1.children_count ORDER BY t1.tree_pos";
	$result=Database::query($myquery);

	$Categories=Database::store_result($result);
}




if($action == 'add' || $action == 'edit')
{
	?>
	<div class="actions">
	<a href="<?php echo api_get_self(); ?>?category=<?php echo Security::remove_XSS($category); ?>"><?php echo Display::return_icon('folder_up.png',get_lang("Back"),'','32'); if(!empty($category)) echo ' ('.Security::remove_XSS($category).')'; ?></a>
	</div>



	<?php
	$form_title = ($action == 'add')?get_lang('AddACategory'):get_lang('EditNode');
	if(!empty($category))
	{
		$form_title .= ' '.get_lang('Into').' '.Security::remove_XSS($category);
	}

	$form = new FormValidator('course_category');
	$form->addElement('header', '', $form_title);
	$form->display();

	?>

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
	  <td><input type="text" name="categoryCode" size="20" maxlength="20" value="<?php echo api_htmlentities(stripslashes($categoryCode),ENT_QUOTES,$charset); ?>" /></td>
	</tr>
	<tr>
	  <td nowrap="nowrap"><?php echo get_lang("CategoryName"); ?> :</td>
	  <td><input type="text" name="categoryName" size="20" maxlength="100" value="<?php echo api_htmlentities(stripslashes($categoryName),ENT_QUOTES,$charset); ?>" /></td>
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
	  <?php
	  	if(isset($_GET['id']) && !empty($_GET['id'])) {
			$class="save";
			$text=get_lang('CategoryMod');
		} else {
			$class="add";
			$text=get_lang('AddCategory');
		}
	  ?>
	  <td><button type="submit" class="<?php echo $class; ?>" value="<?php echo $text; ?>" ><?php echo $text; ?></button></td>
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
	$result=Database::query($myquery);
	$parent_id = 0;
	if(Database::num_rows($result)>0){
		$parent_id=Database::fetch_array($result);
	}

	$parent_id['parent_id']?$link=' ('.$parent_id['parent_id'].')':$link='';
	?>

	<a href="<?php echo api_get_self(); ?>?category=<?php echo $parent_id['parent_id']; ?>"><?php echo Display::return_icon('folder_up.png',get_lang("Back"),'','32'); if(!empty($parent_id)) echo $link ?></a>

	<?php
}
?>
<?php
if(!empty($category)){
	$CategoryInto=' '.get_lang('Into').' '.Security::remove_XSS($category);
}
else{
	$CategoryInto='';
}
?>
<a href="<?php echo api_get_self(); ?>?category=<?php echo Security::remove_XSS($category); ?>&amp;action=add"><?php echo Display::return_icon('new_folder.png',get_lang("AddACategory").$CategoryInto,'','32'); ?></a>
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
		<a href="<?php echo api_get_self(); ?>?category=<?php echo Security::remove_XSS($category); ?>&amp;action=delete&amp;id=<?php echo Security::remove_XSS($enreg['code']); ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)); ?>')) return false;"><?php Display::display_icon('delete.gif', get_lang('DeleteNode'));?></a>
		<a href="<?php echo api_get_self(); ?>?category=<?php echo Security::remove_XSS($category); ?>&amp;action=moveUp&amp;id=<?php echo Security::remove_XSS($enreg['code']); ?>&amp;tree_pos=<?php echo $enreg['tree_pos']; ?>"><?php Display::display_icon('up.gif', get_lang('UpInSameLevel'));?></a>
		<?php echo $enreg['name']; ?>
		(<?php echo $enreg['children_count']; ?> <?php echo get_lang('CategoriesNumber'); ?> - <?php echo $enreg['nbr_courses']; ?> <?php echo get_lang('Courses'); ?>)
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

	$result=Database::query("SELECT parent_id,tree_pos FROM $tbl_category WHERE code='$node'");

	if($row=Database::fetch_array($result))
	{
		if(!empty($row['parent_id']))
		{
			Database::query("UPDATE $tbl_course SET category_code='".$row['parent_id']."' WHERE category_code='$node'");
			Database::query("UPDATE $tbl_category SET parent_id='".$row['parent_id']."' WHERE parent_id='$node'");
		}
		else
		{
			Database::query("UPDATE $tbl_course SET category_code='' WHERE category_code='$node'");
			Database::query("UPDATE $tbl_category SET parent_id=NULL WHERE parent_id='$node'");
		}

		Database::query("UPDATE $tbl_category SET tree_pos=tree_pos-1 WHERE tree_pos > '".$row['tree_pos']."'");
		Database::query("DELETE FROM $tbl_category WHERE code='$node'");

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

	$result=Database::query("SELECT 1 FROM $tbl_category WHERE code='$code'");

	if(Database::num_rows($result))
	{
		return false;
	}

	$result=Database::query("SELECT MAX(tree_pos) AS maxTreePos FROM $tbl_category");

	$row=Database::fetch_array($result);

	$tree_pos=$row['maxTreePos']+1;

	Database::query("INSERT INTO $tbl_category(name,code,parent_id,tree_pos,children_count,auth_course_child) VALUES('$name','$code',".(empty($parent_id)?"NULL":"'$parent_id'").",'$tree_pos','0','$canHaveCourses')");

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
		$result=Database::query("SELECT 1 FROM $tbl_category WHERE code='$code'");

		if(Database::num_rows($result))
		{
			return false;
		}
	}

	Database::query("UPDATE $tbl_category SET name='$name',code='$code',auth_course_child='$canHaveCourses' WHERE code='$old_code'");

	return true;
}

function moveNodeUp($code,$tree_pos,$parent_id)
{
	global $tbl_category;
	$code 		= Database::escape_string($code);
	$tree_pos 	= Database::escape_string($tree_pos);
	$parent_id	= Database::escape_string($parent_id);

	$result=Database::query("SELECT code,tree_pos FROM $tbl_category WHERE parent_id ".(empty($parent_id)?"IS NULL":"='$parent_id'")." AND tree_pos<'$tree_pos' ORDER BY tree_pos DESC LIMIT 0,1");

	if(!$row=Database::fetch_array($result))
	{
		$result=Database::query("SELECT code,tree_pos FROM $tbl_category WHERE parent_id ".(empty($parent_id)?"IS NULL":"='$parent_id'")." AND tree_pos>'$tree_pos' ORDER BY tree_pos DESC LIMIT 0,1");

		if(!$row=Database::fetch_array($result))
		{
			return false;
		}
	}

	Database::query("UPDATE $tbl_category SET tree_pos='".$row['tree_pos']."' WHERE code='$code'");
	Database::query("UPDATE $tbl_category SET tree_pos='$tree_pos' WHERE code='$row[code]'");
}

function updateFils($category)
{
	global $tbl_category;
	$category = Database::escape_string($category);
	$result=Database::query("SELECT parent_id FROM $tbl_category WHERE code='$category'");

	if($row=Database::fetch_array($result))
	{
		updateFils($row['parent_id']);
	}

	$children_count=compterFils($category,0)-1;

	Database::query("UPDATE $tbl_category SET children_count='$children_count' WHERE code='$category'");
}

function compterFils($pere,$cpt)
{
	global $tbl_category;
	$pere = Database::escape_string($pere);
	$result=Database::query("SELECT code FROM $tbl_category WHERE parent_id='$pere'");

	while($row=Database::fetch_array($result))
	{
		$cpt=compterFils($row['code'],$cpt);
	}

	return ($cpt+1);
}
?>
