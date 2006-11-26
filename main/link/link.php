<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003-2005 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
* Main script for the links tool.
*
* Features:
* - Organize links into categories;
* - favorites/bookmarks-like interface;
* - move links up/down within a category;
* - move categories up/down;
* - expand/collapse all categories (except the main "non"-category);
* - add link to 'root' category => category-less link is always visible.
*
* @author Patrick Cool, main author, completely rewritten
* @author Rene Haentjens, added CSV file import (October 2004)
* @package dokeos.link
* @todo improve organisation, tables should come from database library
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = "link";
include("../inc/global.inc.php");
$this_section=SECTION_COURSES;

api_protect_course_script();

$tbl_link = Database::get_course_table(TABLE_LINK);
$tbl_categories = Database::get_course_table(TABLE_LINK_CATEGORY);

$nameTools = get_lang("Links");

//statistics
include(api_get_path(LIBRARY_PATH).'events.lib.inc.php');
event_access_tool(TOOL_LINK);

Display::display_header($nameTools,"Links");

$is_allowedToEdit 	= is_allowed_to_edit();
?>
<script type="text/javascript">
/* <![CDATA[ */
function MM_popupMsg(msg) { //v1.0
  confirm(msg);
}
/* ]]> */
</script>

<?php


//api_display_tool_title($nameTools);

/*
-----------------------------------------------------------
	Introduction section
	(editable by course admins)
-----------------------------------------------------------
*/
Display::display_introduction_section(TOOL_LINK);

include("linkfunctions.php");

$link_submitted = $_POST["submitLink"];
$category_submitted = $_POST["submitCategory"];
$urlview = $_GET["urlview"];
$submitImport = $_POST["submitImport"];
$down = $_GET['down'];
$up = $_GET['up'];
$catmove = $_GET['catmove'];
$editlink = $_REQUEST['editlink'];
$id = $_REQUEST['id'];
$urllink = $_REQUEST['urllink'];
$title = $_REQUEST['title'];
$description = $_REQUEST['description'];
$selectcategory = $_REQUEST['selectcategory'];
$submitLink = $_REQUEST['submitLink'];
$action = $_REQUEST['action'];
$category_title = $_REQUEST['category_title'];
$submitCategory = $_REQUEST['submitCategory'];

// treating the post date by calling the relevant function depending of the action querystring.
switch($_GET['action'])
{
	case "addlink":			if($link_submitted)
							{
								if(!addlinkcategory("link"))	// here we add a link
								{
									unset($submitLink);
								}
							}
							break;
	case "addcategory":		if($category_submitted)
							{
								if(!addlinkcategory("category"))	// here we add a category
								{
									unset($submitCategory);
								}
							}
							break;
	case "importcsv":		if($submitImport) import_csvfile();
							break;
	case "deletelink":		deletelinkcategory("link"); // here we delete a link
							break;
	case "deletecategory":	deletelinkcategory("category"); // here we delete a category
							break;
	case "editlink":		editlinkcategory("link"); // here we edit a link
							break;
	case "editcategory":	editlinkcategory("category"); // here we edit a category
							break;
	case "visible":			change_visibility($_GET['id'],$_GET['scope']); // here we edit a category
							break;	
	case "invisible":		change_visibility($_GET['id'],$_GET['scope']); // here we edit a category
							break;								
}

if($is_allowedToEdit)
{
	echo "<ul>\n";
	echo "<li><a href=\"".$_SERVER['PHP_SELF']."?action=addlink&amp;category=".$category."&amp;urlview=$urlview\">".get_lang("LinkAdd")."</a></li>\n",
		"<li><a href=\"".$_SERVER['PHP_SELF']."?action=addcategory&amp;urlview=".$urlview."\">".get_lang("CategoryAdd")."</a></li>\n",
	   /* "<li><a href=\"".$_SERVER['PHP_SELF']."?action=importcsv&amp;urlview=".$urlview."\">".get_lang('CsvImport')."</a></li>\n", // RH*/
		"</ul>\n\n";

	//displaying the error / status messages if there is one
	if (!empty($catlinkstatus) or !empty($msgErr))
	{
			Display :: display_normal_message($catlinkstatus.$msgErr);

		unset($catlinkstatus);
		unset($msgErr);
	}

	// Displaying the correct title and the form for adding a category or link. This is only shown when nothing
	// has been submitted yet, hence !isset($submitLink)
	if (($_GET['action']=="addlink" or $_GET['action']=="editlink") and !$_POST['submitLink'])
	{
		echo "<h4>";
		if ($_GET['action']=="addlink")
			{echo get_lang("LinkAdd");}
		else
			{echo get_lang("LinkMod");}
		echo "</h4>\n\n";
		if ($category=="")
			{$category=0;}
		echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?action=".$_GET['action']."&amp;urlview=".$urlview."\">";
		if ($_GET['action']=="editlink")
		{
			echo "<input type=\"hidden\" name=\"id\" value=\"".$_GET['id']."\" />";
		}
				
		echo "<table><tr>"
			. "<td align=\"right\">URL<span class=\"required\">*</span>  :</td>"
			. "<td><input type=\"text\" name=\"urllink\" size=\"50\" value=\"" . (empty($urllink)?'http://':htmlentities($urllink)) . "\" /></td>"			. "</tr>";
		echo "<tr>"
				. "<td align=\"right\">" . get_lang("LinkName") . " :</td>"
				. "<td><input type=\"text\" name=\"title\" size=\"50\" value=\"" . htmlentities($title) . "\" /></td>"
				. "</tr>"
				. "<tr>" . 
				"<td align=\"right\" valign=\"top\">" . get_lang("Description") . " :</td>" . 
				"<td><textarea wrap=\"physical\" rows=\"3\" cols=\"50\" name=\"description\">" .
				htmlentities($description) . "</textarea></td></tr>";

		$sqlcategories="SELECT * FROM ".$tbl_categories." ORDER BY display_order DESC";
		$resultcategories = api_sql_query($sqlcategories)or die("Error: " . mysql_error());

		if(mysql_num_rows($resultcategories))
		{
			echo	"<tr><td align=\"right\">".get_lang("Category")." :</td><td>",
					"<select name=\"selectcategory\">",
					"<option value=\"0\">--</option>";

			while ($myrow = mysql_fetch_array($resultcategories))
			{
				echo "<option value=\"".$myrow["id"]."\"";
				if ($myrow["id"]==$category)
					{echo " selected";}
				echo ">".$myrow["category_title"]."</option>";
			}
			echo "</select></td></tr>";
		}

		echo "<tr><td align=\"right\">".get_lang("OnHomepage")." ? </td><td><input class=\"checkbox\" type=\"checkbox\" name=\"onhomepage\" id=\"onhomepage\" value=\"1\" $onhomepage><label for=\"onhomepage\"> ".get_lang("Yes")."</label></td></tr>";
		
		echo "<tr><td></td><td><input type=\"Submit\" name=\"submitLink\" value=\"".get_lang("Ok")."\" /></td></tr>",
			"</table>",
			"</form>";
	}
	elseif(($_GET['action']=="addcategory" or $_GET['action']=="editcategory") and !$submitCategory)
	{
		echo "<h4>";
		if ($_GET['action']=="addcategory")
			{echo get_lang("CategoryAdd");}
		else
			{echo get_lang("CategoryMod");}
		echo "</h4>\n\n";
		echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?action=".$_GET['action']."&amp;urlview=".$urlview."\">";
		if ($_GET['action']=="editcategory")
		{
			echo "<input type=\"hidden\" name=\"id\" value=\"".$id."\" />";
		}
		echo "<table><tr>",
			"<td align=\"right\">".get_lang("CategoryName")."<span class=\"required\">*</span>  :</td>",
			"<td><input type=\"text\" name=\"category_title\" size=\"50\" value=\"",htmlentities($category_title)."\" /></td>",
			"</tr>",
			"<tr><td align=\"right\" valign=\"top\">".get_lang("Description")." :</td>",
			"<td><textarea wrap=\"physical\" rows=\"3\" cols=\"50\" name=\"description\">",htmlentities($description)."</textarea></td></tr>",
			"<tr><td></td><td><input type=\"Submit\" name=\"submitCategory\" value=\"".get_lang("Ok")."\"></td></tr>",
			"</table>",
			"</form>";
	}
	/*elseif(($_GET['action']=="importcsv") and !$submitImport)  // RH start
	{
		echo "<h4>", get_lang('CsvImport'), "</h4>\n\n",
		     "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?action=".$_GET['action']."&amp;urlview=".$urlview."\" enctype=\"multipart/form-data\">",
                // uncomment if you want to set a limit: '<input type="hidden" name="MAX_FILE_SIZE" value="32768">', "\n",
                '<input type="file" name="import_file" size="30">', "\n",
		     	"<input type=\"Submit\" name=\"submitImport\" value=\"".get_lang('Ok')."\">",
		     "</form>";
		echo get_lang('CsvExplain');
	}*/
	echo "<hr/>";
}


//making the show none / show all links. Show none means urlview=0000 (number of zeros depending on the
//number of categories). Show all means urlview=1111 (number of 1 depending on teh number of categories).
$sqlcategories="SELECT * FROM ".$tbl_categories." ORDER BY display_order DESC";
$resultcategories=api_sql_query($sqlcategories);
$aantalcategories = @mysql_num_rows($resultcategories);
echo "<a href=\"".$_SERVER['PHP_SELF']."?urlview=";
	for($j = 1; $j <= $aantalcategories; $j++)
	{
	echo "0";
	}
echo "\">$shownone</a>";
echo " | <a href=\"".$_SERVER['PHP_SELF']."?urlview=";
	for($j = 1; $j <= $aantalcategories; $j++)
	{
	echo "1";
	}
echo "\">$showall</a>";


if (isset($down))
	{
	movecatlink($down);
	}
if (isset($up))
	{
	movecatlink($up);
	}

$sqlcategories="SELECT * FROM ".$tbl_categories." ORDER BY display_order DESC";
$resultcategories=api_sql_query($sqlcategories);

//Starting the table which contains the categories
echo "<table width=\"100%\">";
// displaying the links which have no category (thus category = 0 or NULL), if none present this will not be displayed
	$sqlLinks = "SELECT * FROM ".$tbl_link." WHERE category_id=0 or category_id IS NULL";
	$result = api_sql_query($sqlLinks);
	$numberofzerocategory=mysql_num_rows($result);
	if ($numberofzerocategory!==0)
		{
		echo "<tr><td bgcolor=\"#e6e6e6\"><i>".get_lang('NoCategory')."</i></td></tr>";
		echo "<tr><td>";
		showlinksofcategory(0);
		echo "</td></tr>";
		}
$i=0;
$catcounter=1;
$view="0";
while ($myrow=@mysql_fetch_array($resultcategories))
	{
	if (!isset($urlview))
		{
		// No $view set in the url, thus for each category link it should be all zeros except it's own
		makedefaultviewcode($i);
		}
	else
		{
		$view=$urlview;
		$view[$i]="1";
		}
	// if the $urlview has a 1 for this categorie, this means it is expanded and should be desplayed as a
	// - instead of a +, the category is no longer clickable and all the links of this category are displayed
	$myrow["description"]=text_filter($myrow["description"]);
	if ($urlview[$i]=="1")
		{
		$newurlview=$urlview;
		$newurlview[$i]="0";
		echo "<tr>",
			"<td bgcolor=\"#e6e6e6\"><b>- <a href=\"".$_SERVER['PHP_SELF']."?urlview=".$newurlview."\">".htmlentities($myrow["category_title"])."</a></b><br/>&nbsp;&nbsp;&nbsp;".$myrow["description"];
		if ($is_allowedToEdit)
		{
		showcategoryadmintools($myrow["id"]);
		}
	echo "</td>",
		"</tr>",
		"<tr>",
		"<td>",showlinksofcategory($myrow["id"])."</td>",
		"</tr>";

		}
	else
		{

		echo "<tr><td bgcolor=\"#E6E6E6\"><b>+ <a href=\"".$_SERVER['PHP_SELF']."?urlview=";
		echo is_array($view)?implode('',$view):$view;
		echo "\">".htmlentities($myrow["category_title"])."</a></b><br>&nbsp;&nbsp;&nbsp;";
		echo $myrow["description"];

		if ($is_allowedToEdit)
		{
			showcategoryadmintools($myrow["id"]);
		}
		echo "</td>",
			"</tr>";
		}
	// displaying the link of the category

	$i++;
	}
echo "</table>";

//////////////////////////////////////////////////////////////////////////////

Display::display_footer();

?>