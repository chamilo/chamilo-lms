<?php // $Id: index.php 16620 2008-10-25 20:03:54Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue Notre Dame, 152, B-1140 Evere, Belgium, info@dokeos.com
==============================================================================
*/


/**
==============================================================================
* This is a learning path creation and player tool in Dokeos - previously
* learnpath_handler.php
*
* @author Patrick Cool
* @author Denes Nagy
* @author Roan Embrechts, refactoring and code cleaning
* @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update
* @author Julio Montoya  - Improving the list of templates
* @package dokeos.learnpath
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
$this_section=SECTION_COURSES;

api_protect_course_script();

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
//the main_api.lib.php, database.lib.php and display.lib.php
//libraries are included by default

include('learnpath_functions.inc.php');
//include('../resourcelinker/resourcelinker.inc.php');
include('resourcelinker.inc.php');
//rewrite the language file, sadly overwritten by resourcelinker.inc.php
// name of the language file that needs to be included
$language_file = "learnpath";

/*
-----------------------------------------------------------
	Header and action code
-----------------------------------------------------------
*/
$htmlHeadXtra[] = '
<script type="text/javascript">

var temp=false;
var temp2=false;
var use_document_title='.api_get_setting('use_document_title').';
var load_default_template = '. ((isset($_POST['submit']) || empty($_SERVER['QUERY_STRING'])) ? 'false' : 'true' ) .';

function FCKeditor_OnComplete( editorInstance )
{
	editorInstance.Events.AttachEvent( \'OnSelectionChange\', check_for_title ) ;
	document.getElementById(\'frmModel\').innerHTML = "<iframe height=890px width=230px; frameborder=0 src=\''.api_get_path(WEB_LIBRARY_PATH).'fckeditor/editor/fckdialogframe.html \'>";
}

function check_for_title()
	{
		if(temp==true){
			// This functions shows that you can interact directly with the editor area
			// DOM. In this way you have the freedom to do anything you want with it.

			// Get the editor instance that we want to interact with.
			var oEditor = FCKeditorAPI.GetInstance(\'content_lp\') ;

			// Get the Editor Area DOM (Document object).
			var oDOM = oEditor.EditorDocument ;

			var iLength ;
			var contentText ;
			var contentTextArray;
			var bestandsnaamNieuw = "";
			var bestandsnaamOud = "";

			// The are two diffent ways to get the text (without HTML markups).
			// It is browser specific.

			if( document.all )		// If Internet Explorer.
			{
				contentText = oDOM.body.innerText ;
			}
			else					// If Gecko.
			{
				var r = oDOM.createRange() ;
				r.selectNodeContents( oDOM.body ) ;
				contentText = r.toString() ;
			}

			var index=contentText.indexOf("/*<![CDATA");
			contentText=contentText.substr(0,index);

			// Compose title if there is none
			contentTextArray = contentText.split(\' \') ;
			var x=0;
			for(x=0; (x<5 && x<contentTextArray.length); x++)
			{
				if(x < 4)
				{
					bestandsnaamNieuw += contentTextArray[x] + \' \';
				}
				else
				{
					bestandsnaamNieuw += contentTextArray[x];
				}
			}


		}
		temp=true;
	}

function InnerDialogLoaded()
{
	if (document.all)
	{
		// if is iexplorer
		var B=new window.frames.content_lp___Frame.FCKToolbarButton(\'Templates\',window.content_lp___Frame.FCKLang.Templates);
	}
	else
	{
		var B=new window.frames[0].FCKToolbarButton(\'Templates\',window.frames[0].FCKLang.Templates);
	}

	return 	B.ClickFrame();
};

</script>';

$htmlHeadXtra[] = $_SESSION['oLP']->create_js();
/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/ 

$is_allowed_to_edit = api_is_allowed_to_edit(null,true);

$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_lp_view = Database::get_course_table(TABLE_LP_VIEW);

$isStudentView  = (int) $_REQUEST['isStudentView'];
$learnpath_id   = (int) $_REQUEST['lp_id'];
$submit			= $_POST['submit_button'];
/*
$chapter_id     = $_GET['chapter_id'];
$title          = $_POST['title'];
$description   = $_POST['description'];
$Submititem     = $_POST['Submititem'];
$action         = $_REQUEST['action'];
$id             = (int) $_REQUEST['id'];
$type           = $_REQUEST['type'];
$direction      = $_REQUEST['direction'];
$moduleid       = $_REQUEST['moduleid'];
$prereq         = $_REQUEST['prereq'];
$type           = $_REQUEST['type'];
*/
/*
==============================================================================
		MAIN CODE
==============================================================================
*/
// using the resource linker as a tool for adding resources to the learning path
if ($action=="add" and $type=="learnpathitem")
{
	 $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ( (! $is_allowed_to_edit) or ($isStudentView) )
{
	error_log('New LP - User not authorized in lp_add_item.php');
	header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
//from here on, we are admin because of the previous condition, so don't check anymore

$sql_query = "SELECT * FROM $tbl_lp WHERE id = $learnpath_id";
$result=Database::query($sql_query);
$therow=Database::fetch_array($result);

//$admin_output = '';
/*
-----------------------------------------------------------
	Course admin section
	- all the functions not available for students - always available in this case (page only shown to admin)
-----------------------------------------------------------
*/
/*==================================================
			SHOWING THE ADMIN TOOLS
 ==================================================*/
if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
}

$interbreadcrumb[]= array ("url"=>"lp_controller.php?action=list", "name"=> get_lang("_learning_path"));
$interbreadcrumb[]= array ("url"=>api_get_self()."?action=build&lp_id=$learnpath_id", "name" => stripslashes("{$therow['name']}"));

switch($_GET['type']){
	case 'chapter':
		$interbreadcrumb[]= array ("url"=>"#", "name" => get_lang("NewChapter"));
	break;
	default:
		$interbreadcrumb[]= array ("url"=>"#", "name" => get_lang("NewStep"));
	break;
}

//Theme calls
$show_learn_path=true;
$lp_theme_css=$_SESSION['oLP']->get_theme();

Display::display_header(null,'Path');
//api_display_tool_title($therow['name']);

$suredel = trim(get_lang('AreYouSureToDelete'));
//$suredelstep = trim(get_lang('AreYouSureToDeleteSteps'));
?>
<script type='text/javascript'>
/* <![CDATA[ */
function stripslashes(str) {
	str=str.replace(/\\'/g,'\'');
	str=str.replace(/\\"/g,'"');
	str=str.replace(/\\\\/g,'\\');
	str=str.replace(/\\0/g,'\0');
	return str;
}
function confirmation(name)
{
	name=stripslashes(name);
	if (confirm("<?php echo $suredel; ?> " + name + " ?"))
	{
		return true;
	}
	else
	{
		return false;
	}
}
</script>
<?php

//echo $admin_output;

/*
-----------------------------------------------------------
	DISPLAY SECTION
-----------------------------------------------------------
*/
echo $_SESSION['oLP']->build_action_menu();
echo '<table cellpadding="0" cellspacing="0" class="lp_build">';

	echo '<tr>';

		echo '<td class="tree">';
			// show the template list
			if (isset($_GET['type']) && $_GET['type']=='document' && !isset($_GET['file']))
			{
				$count_items = count($_SESSION['oLP']->ordered_items);
				$style = ($count_items > 12)?' style="height:250px;width:230px;overflow-x : auto; overflow-y : scroll;" ':' class="lp_tree" ';
				echo '<div  '.$style.'>';
				//build the tree with the menu items in it
				echo $_SESSION['oLP']->build_tree();
				echo '</div>';
				// show the template list
				echo '<p style="border-bottom:1px solid #999999; margin:0; padding:2px;"></p>'; //line
				echo '<br />';
				echo '<div id="frmModel" style="display:block; height:890px;width:100px; position:relative;"></div>';
			} else {
				echo '<div class="lp_tree">';
				//build the tree with the menu items in it
				echo $_SESSION['oLP']->build_tree();
				echo '</div>';
			}


		echo '</td>';
		echo '<td class="workspace">';

			if(isset($new_item_id) && is_numeric($new_item_id))
			{
				switch($_GET['type'])
				{

					case 'chapter':
						echo $_SESSION['oLP']->display_manipulate($new_item_id, $_GET['type']);
						Display::display_confirmation_message(get_lang('NewChapterCreated'));
						break;

					case TOOL_LINK:
						echo $_SESSION['oLP']->display_manipulate($new_item_id, $_GET['type']);
						Display::display_confirmation_message(get_lang('NewLinksCreated'));
						break;

					case TOOL_STUDENTPUBLICATION:

						echo $_SESSION['oLP']->display_manipulate($new_item_id, $_GET['type']);
						Display::display_confirmation_message(get_lang('NewStudentPublicationCreated'));
						break;

					case 'module':

						echo $_SESSION['oLP']->display_manipulate($new_item_id, $_GET['type']);
						Display::display_confirmation_message(get_lang('NewModuleCreated'));
						break;

					case TOOL_QUIZ:

						echo $_SESSION['oLP']->display_manipulate($new_item_id, $_GET['type']);
						Display::display_confirmation_message(get_lang('NewExerciseCreated'));
						break;


					case TOOL_DOCUMENT:
						Display::display_confirmation_message(get_lang('NewDocumentCreated'));
						echo $_SESSION['oLP']->display_item($new_item_id, true, $msg);
						break;


					case TOOL_FORUM:
						echo $_SESSION['oLP']->display_manipulate($new_item_id, $_GET['type']);
						Display::display_confirmation_message(get_lang('NewForumCreated'));
						break;


					case 'thread':
						echo $_SESSION['oLP']->display_manipulate($new_item_id, $_GET['type']);
						Display::display_confirmation_message(get_lang('NewThreadCreated'));
						break;

				}
			}
			else
			{
				switch($_GET['type'])
				{
					case 'chapter':

						echo $_SESSION['oLP']->display_item_form($_GET['type'], get_lang("EnterDataNewChapter"));

						break;

					case 'module':

						echo $_SESSION['oLP']->display_item_form($_GET['type'], get_lang("EnterDataNewModule"));

						break;

					case 'document':

						if(isset($_GET['file']) && is_numeric($_GET['file']))
						{
							echo $_SESSION['oLP']->display_document_form('add', 0, $_GET['file']);
						}
						else
						{
							echo $_SESSION['oLP']->display_document_form('add', 0);
						}

						break;

					case 'hotpotatoes':

						echo $_SESSION['oLP']->display_hotpotatoes_form('add', 0, $_GET['file']);

						break;

					case 'quiz':

						echo $_SESSION['oLP']->display_quiz_form('add', 0, $_GET['file']);

						break;

					case 'forum':

						echo $_SESSION['oLP']->display_forum_form('add', 0, $_GET['forum_id']);

						break;

					case 'thread':

						echo $_SESSION['oLP']->display_thread_form('add', 0, $_GET['thread_id']);

						break;

					case 'link':

						echo $_SESSION['oLP']->display_link_form('add', 0, $_GET['file']);

						break;

					case 'student_publication':

						echo $_SESSION['oLP']->display_student_publication_form('add', 0, $_GET['file']);

						break;

					case 'step':

						echo $_SESSION['oLP']->display_resources();

						break;
				}
			}

		echo '</td>';

	echo '</tr>';

echo '</table>';

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>