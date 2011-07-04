<?php
/* For licensing terms, see /license.txt */
/**
* @author Patrick Cool, original code
* @author Denes Nagy - many bugfixes and improvements, adjusted for learning path
* @author Roan Embrechts - refactoring, code cleaning
* @package dokeos.resourcelinker
* @todo reorganise code - This class is used?
* use Database API instead of creating table names locally.
* 
*/

/*
		INIT SECTION
*/
// name of the language file that needs to be included
//$language_file = 'resourcelinker';// TODO: Repeated deleting and moving the rest of this lang file to trad4all
include ('../inc/global.inc.php');
$this_section=SECTION_COURSES;

api_protect_course_script();

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
include_once (api_get_path(LIBRARY_PATH)."fileUpload.lib.php");
include ('resourcelinker.inc.php');

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/

$link_table = Database :: get_course_table(TABLE_LINK);
$item_property_table = Database :: get_course_table(TABLE_ITEM_PROPERTY);

$tbl_learnpath_main = Database :: get_course_table(TABLE_LEARNPATH_MAIN);
$tbl_learnpath_chapter = Database :: get_course_table(TABLE_LEARNPATH_CHAPTER);
$tbl_learnpath_item = Database :: get_course_table(TABLE_LEARNPATH_ITEM);

$action = $_REQUEST['action'];
$add = $_REQUEST['add'];
$chapter_id = $_REQUEST['chapter_id'];
$content = $_REQUEST['content'];
// Note by Patrick Cool: this has been solved belowd. This piece of code hacking produced too much errors.
/*
if(empty($content)){
	//adds a default to the item-type selection
	$content = 'Document';
}
*/
$folder = $_REQUEST['folder'];
$id = $_REQUEST['id'];
$learnpath_id = $_REQUEST['learnpath_id'];
$originalresource = $_REQUEST['originalresource'];
$show_resources = $_REQUEST['show_resources'];
$source_forum = $_REQUEST['source_forum'];
$source_id = $_REQUEST['source_id'];
$target = $_REQUEST['target'];
$external_link = $_REQUEST['external_link'];

$from_learnpath = $_SESSION['from_learnpath'];

// this variable controls wether the link to add a chapter in a module or
// another chapter is shown. This allows to create multi-level learnpaths,
// but export features are not ready for this, yet, so use at your own risks
// default : false -> do not display link
// This setting should be moved to the platform configuration page in time...
$multi_level_learnpath = true;

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
if ($from_learnpath == 'yes')
{
	//start from clear every time in LearnPath Builder
	$_SESSION['addedresource'] = null;
	$_SESSION['addedresourceid'] = null;
	$_SESSION['addedresourceassigned'] = null;
	unset ($_SESSION['addedresource']);
	unset ($_SESSION['addedresourceid']);
	unset ($_SESSION['addedresourceassigned']);
}

// Process a new chapter?
if (!empty ($_POST['add_chapter']) && !empty ($_POST['title']))
{
	$title = $_POST['title'];
	$description = '';
	if (!empty ($_POST['description']))
	{
		$description = $_POST['description'];
	}

	// get max display_order so far in this parent chapter
	$sql = "SELECT MAX(display_order) FROM $tbl_learnpath_chapter WHERE learnpath_id = $learnpath_id "." AND parent_chapter_id = $chapter_id";
	$res = Database::query($sql);
	$row = Database::fetch_array($res);
	$max_temp = $row[0];

	$sql = "SELECT MAX(display_order) FROM $tbl_learnpath_item WHERE "." chapter_id = $chapter_id";
	$res = Database::query($sql);
	$row = Database::fetch_array($res);
	$max_temp2 = $row[0];
	if ($max_temp2 > $max_temp)
	{
		$order = $max_temp2 +1;
	}
	else
	{
		$order = $max_temp +1;
	}

	$sql = "INSERT INTO $tbl_learnpath_chapter "."(learnpath_id,chapter_name,chapter_description,parent_chapter_id,display_order) "." VALUES "."($learnpath_id, '$title', '$description', $chapter_id, $order )";
	$res = Database::query($sql);
	if ($res !== false)
	{
		$title = '';
		$description = '';
	}
}

// This if when a external link is submitted
if (!empty ($_POST['external_link_submit']))
{
	$add = true;
	if ($add_2_links != "niet toevoegen")
	{
		// add external link to the links table.
		$pos = strpos($external_link, 'ttp:');
		if ($pos == '')
		{
			$external_link = 'http://'.$external_link;
		}

		$sql = "INSERT INTO $link_table (url, title, category_id) VALUES ('$external_link','$external_link','$add_2_links')";
		$result = Database::query($sql);
		$addedresource[] = "Link";
		$addedresourceid[] = Database::insert_id();
		$_SESSION['addedresource'] = $addedresource;
		$_SESSION['addedresourceid'] = $addedresourceid;
	}
	else
	{
		// do not add external link to the links table
		$addedresource[] = "Externallink";
		$addedresourceid[] = $external_link;
		$_SESSION['addedresource'] = $addedresource;
		$_SESSION['addedresourceid'] = $addedresourceid;
	}
}

// loading the session variables into local array
$addedresource = $_SESSION['addedresource'];
$addedresourceid = $_SESSION['addedresourceid'];

// This is when a resource was added to the session
if ($add)
{
	// adding the new variable to the local array
	if (empty ($_POST['external_link_submit']))
	{
		//that case is already arranged, see upwards
		$addedresource[] = $content;
		$addedresourceid[] = $add;
	}
	$addedresourceassigned[] = 0;

	// loading the local array into the session variable
	$_SESSION['addedresource'] = $addedresource;
	$_SESSION['addedresourceid'] = $addedresourceid;

	//---------------------------------------
	//we assign to chapters immediately !
	//---------------------------------------
	$resource_added = false;
	if ($from_learnpath == 'yes')
	{
		$i = 0;
		//calculating the last order of the items of this chapter
		$sql = "SELECT MAX(display_order) FROM $tbl_learnpath_item WHERE chapter_id=$chapter_id";
		$result = Database::query($sql);
		if(Database::num_rows($result)==0){
			$lastorder_item = 0;
		}else{
			$row = Database::fetch_array($result);
			$lastorder_item = ($row[0]);
		}
		$sql = "SELECT MAX(display_order) FROM $tbl_learnpath_chapter WHERE parent_chapter_id=$chapter_id";
		$result = Database::query($sql);
		if(Database::num_rows($result)==0){
			$lastorder_chapter = 0;
		}else{
			$row = Database::fetch_array($result);
			$lastorder_chapter = ($row[0]);
		}
		$lastorder = ($lastorder_chapter>$lastorder_item?$lastorder_chapter+1:$lastorder_item+1);

		foreach ($addedresource as $addedresource_item)
		{
			// in the case we added a chapter, add this into the chapters list with the correct parent_id
			if ($addedresource_item == "Chap")
			{
				$sql = "INSERT INTO $tbl_learnpath_chapter ("."'learnpath_id','chapter_name','chapter_description','parent_chapter_id','display_order'".") VALUES (".$learnpath_id.",'".$learnpath_chapter_name."','".$learnpath_chapter_description."',".$chapter_id.",".$lastorder.")";
				Database::query($sql);
			}

			if (!$addedresourceassigned[$i])
			{
				//not to assign it twice
				if ($addedresource_item == "Ass")
				{
					$addedresource_item = "Assignments";
				}
				if ($addedresource_item == "Drop")
				{
					$addedresource_item = "Dropbox";
				}
				if ($addedresource_item == "Intro")
				{
					$addedresource_item = "Introduction_text";
				}
				if ($addedresource_item == "Course_desc")
				{
					$addedresource_item = "Course_description";
				}
				if ($addedresource_item == "Group")
				{
					$addedresource_item = "Groups";
				}
				if ($addedresource_item == "User")
				{
					$addedresource_item = "Users";
				}
				if ($target == '')
				{
					$target = '_self';
				}
				if ($addedresource_item == 'Link')
				{
					$addedresource_item .= ' '.$target;
				}
				$sql = "INSERT INTO $tbl_learnpath_item (id, chapter_id, item_type, item_id, display_order) VALUES ( '$autoid', '$chapter_id', '$addedresource_item','$addedresourceid[$i]','".$lastorder."')";
				$result = Database::query($sql);
				$addedresourceassigned[$i] = 1;
				$resource_added = true;
			}
			$i ++;
			$lastorder ++;
		}
		//$_SESSION['addedresource']=null;
		//$_SESSION['addedresourceid']=null;
		// cleaning up the session once again
		$_SESSION['addedresource'] = null;
   		$_SESSION['addedresourceid'] = null;
   		$_SESSION['addedresourceassigned'] = null;
   		unset ($_SESSION['addedresource']);
   		unset ($_SESSION['addedresourceid']);
   		unset ($_SESSION['addedresourceassigned']);
	}
}

/*
==============================================================================
	BREADCRUMBS
	This part is to allow going back to the tool where you came from
	in a previous version I used the table tool_list, but since the forum can access the
	resource_linker from two different pages (newtopic.php and editpost.php) and this is different
	from the link field in tool_list, I decide to hardcode this stuff here.
	By doing this, you can easily control which pages can access the toollinker and which not.
==============================================================================
*/
if ($_GET["source_id"])
{
	switch ($_GET["source_id"])
	{
		case "1" : // coming from Agenda
			if ($action == "edit")
			{
				$url = "../calendar/agenda.php?action=edit&id=49&originalresource=$originalresource";
			}
			elseif ($action == "add")
		{
				$url = "../calendar/agenda.php?action=add&originalresource=$originalresource";
			}
			else
			{
				$url = "../calendar/agenda.php?action=add";
			}
			$originaltoolname = get_lang("Agenda");
			$breadcrumbelement = array ("url" => $url, "name" => $originaltoolname);
			session_unregister('from_learnpath');
			unset ($from_learnpath);
			break;
		case "2" : // coming from forum: new topic
			$url = "../phpbb/newtopic.php?forum=$source_forum&md5=$md5";
			$originaltoolname = get_lang("ForumAddNewTopic");
			$breadcrumbelement = array ("url" => $url, "name" => $originaltoolname);
			session_unregister('from_learnpath');
			unset ($from_learnpath);
			break;
		case "3" : // coming from forum: edit topic
			$url = "../phpbb/editpost.php?post_id=$post_id&topic=$topic&forum=$forum&md5=$md5&originalresource=no";
			$originaltoolname = get_lang("ForumEditTopic");
			$breadcrumbelement = array ("url" => $url, "name" => $originaltoolname);
			session_unregister('from_learnpath');
			unset ($from_learnpath);
			break;
		case "4" : // coming from exercises: edit topic
			$url = "../exercice/admin.php?modifyAnswers=$modifyAnswers";
			$originaltoolname = get_lang("ExerciseAnswers");
			$breadcrumbelement = array ("url" => $url, "name" => $originaltoolname);
			session_unregister('from_learnpath');
			unset ($from_learnpath);
			break;
		case "5" : // coming from learning path
			$from_learnpath = 'yes';
			api_session_register('from_learnpath');
			break;
		case "6" : // coming from forum: reply
			$url = "../phpbb/reply.php?topic=$topic&forum=$forum&parentid=$parentid";
			$url = $_SESSION['origintoolurl'];
			$originaltoolname = get_lang("ForumReply");
			$breadcrumbelement = array ("url" => $url, "name" => $originaltoolname);
			session_unregister('from_learnpath');
			unset ($from_learnpath);
			break;
			/*************************************** add Frederik.Vermeire@pandora.be *************************************/

		case "7" : // coming from Ad_Valvas
			if ($action == "edit")
			{
				$url = "../announcements/announcements.php?action=edit&id=49&originalresource=$originalresource";
			}
			elseif ($action == "add")
		{
				$url = "../announcements/announcements.php?action=add&originalresource=$originalresource";
			}
			else
			{
				$url = "../announcements/announcements.php?action=add";
			}
			$originaltoolname = get_lang("AdValvas");
			$breadcrumbelement = array ("url" => $url, "name" => $originaltoolname);
			session_unregister('from_learnpath');
			unset ($from_learnpath);
			break;
			/*************************************** end add Frederik.Vermeire@pandora.be *********************************/

	}
	// We do not come from the learning path. We store the name of the tool & url in a session.
	if ($from_learnpath != 'yes')
	{
		if (!$_SESSION["origintoolurl"] OR $_SESSION["origintoolurl"]<>$interbreadcrumb["url"])
		{
			$_SESSION["origintoolurl"] = $breadcrumbelement["url"];
			$_SESSION["origintoolname"] = $breadcrumbelement["name"];
			$interbreadcrumb = "";
		}
	}

}

// This part of the code is the actual breadcrumb mechanism. If we do not come from the learning path we use
// the information from the session. Else we use the information of the learningpath itself.
if ($from_learnpath != 'yes')
{
	$nameTools = get_lang('AddResource');
	$interbreadcrumb[] = array ("url" => $_SESSION["origintoolurl"], "name" => $_SESSION["origintoolname"]);
}
else
{
	$learnpath_select_query = "	SELECT * FROM $tbl_learnpath_main
		  								WHERE learnpath_id=$learnpath_id";
	$sql_result = Database::query($learnpath_select_query);
	$therow = Database::fetch_array($sql_result);

	$learnpath_chapter_query = "	SELECT * FROM $tbl_learnpath_chapter
		  								WHERE (learnpath_id = '$learnpath_id' and id = '$chapter_id')";
	$sql_result = Database::query($learnpath_chapter_query);
	$therow2 = Database::fetch_array($sql_result);

	$from_learnpath = 'yes';
	session_register('from_learnpath');
	$interbreadcrumb[] = array ("url" => "../scorm/scormdocument.php", "name" => get_lang('LearningPath'));
	$interbreadcrumb[] = array ("url" => "../learnpath/learnpath_handler.php?learnpath_id=$learnpath_id", "name" => "{$therow['learnpath_name']}");
	$interbreadcrumb[] = array ("url" => api_get_self()."?action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no", "name" => "{$therow2['chapter_name']}");

}

$htmlHeadXtra[] = '<script type="text/javascript">
/* <![CDATA[ */
	function targetfunc(input)
	{
		window.location=window.location+"&amp;target="+document.learnpath_link.target.value;
	}
/* ]]> */
</script>';

Display :: display_header($nameTools);

echo "<h3>".$nameTools;
if ($from_learnpath == 'yes')
{
	echo get_lang("AddResource")." - {$therow2['chapter_name']}";
}
echo "</h3>";

// we retrieve the tools that are active.
// We use this to check which resources a student may add (only the modules that are active)
// see http://www.dokeos.com/forum/viewtopic.php?t=4858
$active_modules=array();
$tool_table = Database::get_course_table(TABLE_TOOL_LIST);
$sql_select_active="SELECT * FROM $tool_table WHERE visibility='1'";
$result_select_active=Database::query($sql_select_active);
while ($row=Database::fetch_array($result_select_active))
{
	$active_modules[]=$row['name'];
}


?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="300" valign="top" style='padding-right:15px;'>
      <table width="300" border="0" cellspacing="0" cellpadding="0" style='border-right:1px solid grey;'>
		<?php if ($from_learnpath != 'yes') { ?>
		<tr>
          <td width="26%"><b><?php echo get_lang('CourseResources'); ?></b></td>
        </tr>
        <?php
        if (is_allowed_to_edit() OR in_array(TOOL_DOCUMENT,$active_modules))
        {
        ?>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Document&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Document')."</a>"; ?></td>
        </tr>
        <?php
        }
        if (is_allowed_to_edit() OR in_array(TOOL_CALENDAR_EVENT,$active_modules))
        {
        ?>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Agenda&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Agenda')."</a>"; ?></td>
        </tr>
        <?php
        }
        if (is_allowed_to_edit() OR in_array(TOOL_ANNOUNCEMENT,$active_modules))
        {
        ?>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Ad_Valvas&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('AdValvas')."</a>"; ?></td>
        </tr>
        <?php
        }
        if (is_allowed_to_edit() OR in_array(TOOL_BB_FORUM,$active_modules))
        {
        ?>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Forum&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Forum')."</a>"; ?></td>
        </tr>
        <?php
        }
        if (is_allowed_to_edit() OR in_array(TOOL_LINK,$active_modules))
        {
        ?>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Link&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Link')."</a>"; ?></td>
        </tr>
        <?php
        }
        if (is_allowed_to_edit() OR in_array(TOOL_QUIZ,$active_modules))
        {
        ?>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Exercise&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Exercise')."</a>"; ?></td>
        </tr>

		<?php
        }

}
else
{
?>



		<tr>
          <td width="26%"><b><?php echo get_lang('ExportableCourseResources'); ?></b></td>
        </tr>
<?php if ($multi_level_learnpath === true ) { ?>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=chapter&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Chapter')."</a>"; ?></td>
        </tr>
<?php } ?>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Document&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Document')."</a>"; ?></td>
        </tr>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Exercise&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Exercise')."</a>"; ?></td>
        </tr>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Link&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Link')."</a>"; ?></td>
        </tr>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Forum&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Forum')."</a>"; ?></td>
        </tr>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Agenda&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Agenda')."</a>"; ?></td>
        </tr>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Ad_Valvas&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('AdValvas')."</a>"; ?></td>
        </tr>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Course_description&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('CourseDescription')."</a>"; ?></td>
        </tr>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Introduction_text&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('IntroductionText')."</a>"; ?></td>
        </tr>
		<tr>
          <td>&nbsp;</td>
        </tr>
		<tr>
          <td width="26%"><b><?php echo get_lang('DokeosRelatedCourseMaterial'); ?></b></td>
        </tr>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Dropbox&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Dropbox')."</a>"; ?></td>
        </tr>
		<tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Assignment&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Assignments')."</a>"; ?></td>
        </tr>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Groups&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Groups')."</a>"; ?></td>
        </tr>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Users&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('Users')."</a>"; ?></td>
        </tr>

		<?php

}
?>



		<tr>
          <td>&nbsp;</td>
        </tr>
        <tr>
          <td><b><?php echo get_lang("ExternalResources"); ?></b></td>
        </tr>
        <tr>
          <td><?php echo "<a href=\"".api_get_self()."?content=Externallink&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('ExternalLink')."</a>"; ?></td>
        </tr>
		<?php

if ($from_learnpath != 'yes')
{
	echo "<tr><td>&nbsp;</td></tr>";
	echo "<tr><td><b>".get_lang('ResourcesAdded')." (";
	echo count($addedresource);
	echo ")</b></td></tr>";
	echo "<tr><td nowrap><a href=\"".api_get_self()."?showresources=true&action=$action&id=$id&learnpath_id=$learnpath_id&chapter_id=$chapter_id&source_forum=$source_forum&originalresource=no\">".get_lang('ShowDelete')."</a>";
	echo "</td></tr>";
}
?>
        <tr>
          <td>&nbsp;</td>
        </tr>
        <!--<tr>
          <td><b><?php echo get_lang('BackTo'); ?></b></td>
        </tr>//-->
        <tr>
          <td>

		  <?php

if ($from_learnpath != 'yes')
{
	echo "<form method=\"post\" action=\"{$_SESSION['origintoolurl']}\" style=\"margin: 0px;\"><input type=\"submit\" value=\"".get_lang('Ok')."\"></form>";
}
else
{
	echo "<form method=\"get\" action=\"../learnpath/learnpath_handler.php\" style=\"margin: 0px;\"><input type=\"hidden\" name=\"learnpath_id\" value=\"".htmlentities($learnpath_id)."\"><input type=\"submit\" value=\"".'  '.get_lang('Ok').'  '."\"></form>";
}
?>

          </td>
        </tr>
        <tr>
          <td>&nbsp;</td>
        </tr>
      </table>
    </td>
    <td valign="top">
      <?php

if ($resource_added)
{
	Display :: display_normal_message(get_lang("ResourceAdded"));
}

if ($from_learnpath != 'yes')
{
	echo count($addedresource)." ".api_strtolower(get_lang('ResourcesAdded'))."<br/>";
}
//echo "<hr>";

// Agenda items -->
if ($content == "Agenda")
{
	$TABLEAGENDA 			= Database::get_course_table(TABLE_AGENDA);
	$TABLE_ITEM_PROPERTY 	= Database::get_course_table(TABLE_ITEM_PROPERTY);

	$sql="SELECT agenda.*, toolitemproperties.*
					FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND toolitemproperties.to_group_id='0'
					AND toolitemproperties.visibility='1'";

	$result = Database::query($sql);

	while ($myrow = Database::fetch_array($result))
	{
		echo "<table width=\"100%\"><tr><td bgcolor=\"#E6E6E6\">";
		echo "<img src='../img/agenda.gif' alt='agenda'>";
		echo api_convert_and_format_date($myrow["start_date"], null, date_default_timezone_get())."<br />";
		echo "<b>".$myrow["title"]."</b></td></tr><tr><td>";
		echo $myrow["content"]."<br />";
		showorhide_addresourcelink($content, $myrow["id"]);
		echo "</td></tr></table><br />";
	}
} // end if ($_GET["resource"]=="Agenda")

/*
-----------------------------------------------------------
	chapter
-----------------------------------------------------------
*/
if ($content == "chapter")
{
	echo '<table><form name="add_chapter" action="'.'" method="POST">'."\n";
	echo '  <tr><td>'.get_lang('Title').'</td><td><input type="text" name="title" value="'.$title.'"></input></td></tr>'."\n";
	echo '  <tr><td>'.get_lang('Description').'</td><td><input type="text" name="description" value="'.$description.'"></input></td></tr>'."\n";
	echo '  <tr><td></td><td><input type="submit" name="add_chapter" value="'.get_lang('AddIt').'"/></td></tr>'."\n";
	echo '</form></table>'."\n";
	//echo "<hr>";
}

/*
-----------------------------------------------------------
	Documents
-----------------------------------------------------------
*/
// We show the documents in the following cases
// 1. the link to add documenets in the resource linker was clicked
// 2. we come to the resource linker for the first time (documents = default). In this case it can only be shown if
//  			a. one is a teacher (documents can be shown even if the tool is inactive)
//				b. one is a student AND the documents tool is active. Student cannot add documents if the documents tool is inactive (teacher can do this)
if ($content == "Document" OR (empty($content) AND (is_allowed_to_edit() OR in_array(TOOL_DOCUMENT,$active_modules))) AND !$_GET['showresources'])
{
	// setting variables for file locations
	$baseServDir = $_configuration['root_sys'];
	$courseDir = $_course['path']."/document";
	$baseWorkDir = $baseServDir.$courseDir;
	// showing the link to move one folder up (when not in the root folder)
	show_folder_up();
	// showing the blue bar with the path in it when we are not in the root
	if (get_levels($folder))
	{
		echo "<table width=\"100%\"><tr><td bgcolor=\"#4171B5\">";
		echo "<img src=\"../img/opendir.gif\" alt='directory'><font color=\"#ffffff\"><b>";
		echo $folder."</b></font></td></tr></table>";
	}

	// showing the documents and subfolders of the folder we are in.
	show_documents($folder);
	//echo "<hr>";
}

/*
-----------------------------------------------------------
	Ad Valvas
-----------------------------------------------------------
*/
if ($content == "Ad_Valvas")
{
	$tbl_announcement = Database :: get_course_table(TABLE_ANNOUNCEMENT);
	$sql = "SELECT * FROM ".$tbl_announcement." a, ".$item_property_table." i  WHERE i.tool = '".TOOL_ANNOUNCEMENT."' AND a.id=i.ref AND i.visibility='1' AND i.to_group_id = 0 AND i.to_user_id IS NULL ORDER BY a.display_order ASC";

	$result = Database::query($sql);
	while ($myrow = Database::fetch_array($result))
	{
		echo "<table width=\"100%\"><tr><td>";
		echo "<img src='../img/valves.gif' alt='advalvas'>";
		echo api_convert_and_format_date($myrow["end_date"], DATE_FORMAT_LONG, date_default_timezone_get());
		echo "</td></tr><tr><td>";
		echo $myrow["title"]."<br />";
		showorhide_addresourcelink($content, $myrow["id"]);
		echo "</td></tr></table>";
	}
}

/*
-----------------------------------------------------------
	Forums
-----------------------------------------------------------
*/
if ($content == "Forum")
{
	$TBL_FORUMS 		= Database::get_course_table(TABLE_FORUM);
	$TBL_CATAGORIES 	= Database::get_course_table(TABLE_FORUM_CATEGORY);
	$TBL_FORUMTOPICS 	= Database::get_course_table(TABLE_FORUM_POST);
	$tbl_posts 			= Database::get_course_table(TABLE_FORUM_POST);
	$tbl_posts_text 	= Database::get_course_table(TOOL_FORUM_POST_TEXT_TABLE);

	echo "<table width='100%'>";

	// displaying the categories and the forums
	if (!$forum and !$thread)
	{
		$sql = "SELECT * FROM ".$TBL_FORUMS." forums, ".$TBL_CATAGORIES." categories WHERE forums.cat_id=categories.cat_id ORDER BY forums.cat_id DESC";
		$result = Database::query($sql);
		while ($myrow = Database::fetch_array($result))
		{
			if ($myrow["cat_title"] !== $old_cat_title)
			{
				echo "<tr><td bgcolor='#4171B5' colspan='2'><font color='white'><b>".$myrow["cat_title"]."</b></font></td></tr>";
			}
			$old_cat_title = $myrow["cat_title"];
			echo "<tr><td><img src='../img/forum.gif'><a href='".api_get_self()."?content=Forum&category=".$myrow["cat_id"]."&forum=".$myrow["forum_id"]."&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no'>".$myrow["forum_name"]."</td><td>";
			showorhide_addresourcelink("Forum", $myrow["forum_id"]);
			echo "</td></tr>";
		}
	}
	//displaying all the threads of one forum
	if ($forum)
	{
		// displaying the category title
		$sql = "SELECT * FROM ".$TBL_CATAGORIES." WHERE cat_id=$category";
		$result = Database::query($sql);
		$myrow = Database::fetch_array($result);
		echo "<tr><td bgcolor='#4171B5' colspan='2'><font color='white'><b>".$myrow["cat_title"]."</b></font></td></tr>";

		// displaying the forum title
		$sql = "SELECT * FROM ".$TBL_FORUMS." forums, ".$TBL_FORUMTOPICS." topics WHERE forums.forum_id=topics.forum_id";
		$result = Database::query($sql);
		$myrow = Database::fetch_array($result);
		echo "<tr><td bgcolor='#cccccc' colspan='2'><b>".$myrow["forum_name"]."</b></td></tr>";

		if (!$thread)
		{
			// displaying all the threads of this forum
			$sql = "SELECT * FROM ".$TBL_FORUMTOPICS." WHERE forum_id=$forum";
			$result = Database::query($sql);
			while ($myrow = Database::fetch_array($result))
			{
				echo "<tr><td><a href='".api_get_self()."?content=Forum&category=$category&forum=1&thread=".$myrow["topic_id"]."&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no'>".$myrow["topic_title"]."</a>  (".$myrow["prenom"]." ".$myrow["nom"].")</td><td>";
				showorhide_addresourcelink("Thread", $myrow["topic_id"]);
				echo "</td></tr>";
			}
		}
		else
		{
			// displaying all the replies
			$sql = "SELECT * FROM ".$tbl_posts." post, ".$tbl_posts_text." post_text WHERE post_text.post_id=post.post_id and post.topic_id=$thread ORDER BY post_text.post_id ASC";
			$result = Database::query($sql);
			while ($myrow = Database::fetch_array($result))
			{
				echo "<tr><td><b>".$myrow["post_title"]."</b><br>";
				echo $myrow["post_text"]."</td>";
				echo "<td>";
				showorhide_addresourcelink("Post", $myrow["post_id"]);
				echo "</td></tr><tr><td colspan='2'><hr noshade></td></tr>";
			}

		}
	}
	echo "</table>";
}

/*
-----------------------------------------------------------
	Links
-----------------------------------------------------------
*/
if ($content == "Link")
{
	// including the links language file
	include ("../lang/$language/link.inc.php");

	// including the links functions file
	require_once api_get_path(LIBRARY_PATH).'link.lib.php';

	$tbl_categories = Database::get_course_table(TABLE_LINK_CATEGORY);
	if (($learnpath_id != '') and ($content == 'Link'))
	{
		echo "<form name='learnpath_link'><table>";
		echo "<tr></td><td align='left'>".get_lang('LinkTarget')." :</td><td align='left'><select name='target' onchange='targetfunc()'><option value='_self' ";
		if ($target == '_self')
		{
			echo "selected";
		}
		echo ">".get_lang('SameWindow')."</option><option value='_blank'";
		if ($target == '_blank')
		{
			echo "selected";
		}
		echo ">".get_lang('NewWindow')."</option></select></td></tr></table></form>";
	}

	// showing the links that are in the root (having no category)
	$sql = "SELECT * FROM ".$link_table." l, ".$item_property_table." ip WHERE (l.category_id=0 or l.category_id IS NULL) AND ip.tool = '".TOOL_LINK."' AND l.id=ip.ref AND ip.visibility='1'";
	$result = Database::query($sql);
	if (Database::num_rows($result) > 0)
	{
		echo "<table width=\"100%\"><tr><td bgcolor=\"#E6E6E6\"><i>".get_lang('NoCategory')."</i></td></tr></table>";
		while ($myrow = Database::fetch_array($result))
		{
			echo "<img src='../img/links.gif'>".$myrow["title"];
			echo "<br>";
			showorhide_addresourcelink($content, $myrow["id"]);
			echo "<br><br>";
		}
	}

	// showing the categories and the links in it.
	$sqlcategories = "SELECT * FROM ".$tbl_categories." ORDER by display_order DESC";
	$resultcategories = Database::query($sqlcategories) or die;
	while ($myrow = @ Database::fetch_array($resultcategories))
	{
		$sql_links = "SELECT * FROM ".$link_table." l, ".$item_property_table." ip WHERE l.category_id='".$myrow["id"]."' AND ip.tool = '".TOOL_LINK."' AND l.id=ip.ref AND ip.visibility='1' ORDER BY l.display_order DESC";
		echo "<table width=\"100%\"><tr><td bgcolor=\"#E6E6E6\"><i>".$myrow["category_title"]."</i></td></tr></table>";
		$result_links = Database::query($sql_links);
		while ($myrow = Database::fetch_array($result_links))
		{
			echo "<img src='../img/links.gif' />".$myrow["title"];
			echo "<br>";
			showorhide_addresourcelink($content, $myrow["id"]);
			echo "<br><br>";
		}
	}
}

/*
-----------------------------------------------------------
	Exercise
-----------------------------------------------------------
*/
if (($content == "Exercise") or ($content == "HotPotatoes"))
{
	$TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
	$result = Database::query("SELECT * FROM ".$TBL_EXERCICES." WHERE active='1' ORDER BY id ASC");
	while ($myrow = Database::fetch_array($result))
	{
		echo "<img src='../img/quiz.gif'>".$myrow["title"]."<br>";
		showorhide_addresourcelink($content, $myrow["id"]);
		echo "<br><br>";
	}

	if ($from_learnpath == 'yes')
	{
		$uploadPath = "/HotPotatoes_files";
		$TBL_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
		$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
		$sql = "SELECT * FROM ".$TBL_DOCUMENT." WHERE (path LIKE '%htm%' OR path LIKE '%html%') AND path LIKE '".$uploadPath."/%/%' ORDER BY id ASC";
		$result = Database::query($sql);
		while ($myrow = Database::fetch_array($result))
		{
			$path = $myrow["path"];
			echo "<img src='../img/jqz.gif'>".GetQuizName($path, $documentPath)."<br>";
			showorhide_addresourcelink("HotPotatoes", $myrow["id"]);
			echo "<br><br>";

		}
	}
}

/*
-----------------------------------------------------------
	External Links
-----------------------------------------------------------
*/
if ($content == "Externallink")
{
?>
  <form name="form1" method="post" action="">
  <table width="80%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td align="right"><?php echo get_lang('ExternalLink'); ?> : &nbsp;</td>
      <td align="left"><input name="external_link" type="text" id="external_link" value="http://"></td>
	  <?php

	if ($learnpath_id != '')
	{
		echo "</tr><tr><td align='right'>".get_lang('LinkTarget')." :</td><td align='left'><select name='target'><option value='_self'>".get_lang('SameWindow')."</option><option value='_blank'>".get_lang('NewWindow')."</option></select></td>";
	}
?>
	</tr>
    <tr>
      <td><?php if ($is_allowedToEdit) {echo get_lang('AddToLinks');} ?></td>
      <td>
  	  <?php if ($is_allowedToEdit){?>
	  <select name="add_2_links" id="add_2_links">
      <option value="niet toevoegen" selected="selected">-<?php echo get_lang('DontAdd'); ?>-</option>
	  <option value="0"><?php echo get_lang('MainCategory'); ?></option>
		<?php

	$tbl_categories = Database::get_course_table(TABLE_LINK_CATEGORY);
	$sql = "SELECT * FROM $tbl_categories ORDER BY display_order ASC";
	echo $sql;
	$result = Database::query($sql);
	while ($row = Database::fetch_array($result))
	{
		echo "<option value='".$row["id"]."'>".$row["category_title"]."</option>";
	}
?>

      </select><?php } ?></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td><input name="external_link_submit" type="submit" id="external_link_submit" value="<?php echo get_lang('AddIt'); ?>"></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td colspan="2">&nbsp;</td>
    </tr>
  </table>
</form>
	<?php

}

/*
-----------------------------------------------------------
	Assignments
-----------------------------------------------------------
*/
if ($content == "Assignment")
{
	echo "<a href=".api_get_self()."?content=Ass&add=Ass&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no>".get_lang('AddAssignmentPage')."</a>";
}

/*
-----------------------------------------------------------
	Dropbox
-----------------------------------------------------------
*/
if ($content == "Dropbox")
{
	echo "<a href='".api_get_self()."?content=Drop&add=Drop&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no'>".get_lang('DropboxAdd')."</a>";
}

/*
-----------------------------------------------------------
	Introduction text
-----------------------------------------------------------
*/
if ($content == "Introduction_text")
{
	echo "<a href='".api_get_self()."?content=Intro&add=Intro&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no'>".get_lang('IntroductionTextAdd')."</a>";
}

/*
-----------------------------------------------------------
	Course description
-----------------------------------------------------------
*/
if ($content == "Course_description")
{
	echo "<a href='".api_get_self()."?content=Course_desc&add=Course_desc&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no'>".get_lang('CourseDescriptionAdd')."</a>";
}

/*
-----------------------------------------------------------
	Groups
-----------------------------------------------------------
*/
if ($content == "Groups")
{
	echo "<a href='".api_get_self()."?content=Group&add=Group&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no'>".get_lang('$GroupsAdd')."</a>";
}

/*
-----------------------------------------------------------
	Users
-----------------------------------------------------------
*/
if ($content == "Users")
{
	echo "<a href='".api_get_self()."?content=User&add=User&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no'>".get_lang('UsersAdd')."</a>";
}

if ($showresources)
{
	//echo "<h4>".get_lang('ResourceAdded')."</h4>";
	display_resources(1);
}

echo "</td></tr></table>";

/*
		FOOTER
*/
Display :: display_footer();
?>