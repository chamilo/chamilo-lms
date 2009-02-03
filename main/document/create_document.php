<?php // $Id: create_document.php 18203 2009-02-03 18:02:16Z ndieschburg $

/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
	
==============================================================================
*/
/**
==============================================================================
*	This file allows creating new html documents with an online WYSIWYG html380
*	editor.
*
*	@package dokeos.document
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

// name of the language file that needs to be included 
$language_file = 'document';
include ('../inc/global.inc.php');
$_SESSION['whereami'] = 'document/create';
$this_section = SECTION_COURSES;

$htmlHeadXtra[]='<script>
	
function InnerDialogLoaded()
{
	var B=new window.frames[0].FCKToolbarButton(\'Templates\',window.frames[0].FCKLang.Templates);
	return B.ClickFrame();
};	

	var temp=false;
	var temp2=false;
	var use_document_title='.api_get_setting('use_document_title').';
	var load_default_template = '. ((isset($_POST['submit']) || empty($_SERVER['QUERY_STRING'])) ? 'false' : 'true' ) .';

	function launch_templates()
	{		
		//document.getElementById(\'frmModel\').style.display="block";				
		//document.getElementById(\'content___Frame\').width=\'70%\'; 		
		//window.frames[0].FCKToolbarItems.GetItem("Template").Click;
	}
	
	function FCKeditor_OnComplete( editorInstance )
	{
		editorInstance.Events.AttachEvent( \'OnSelectionChange\', check_for_title ) ;
		document.getElementById(\'frmModel\').innerHTML = "<iframe height=950px; width=97%; id=\"template_iframe\" frameborder=0 src=\''.api_get_path(WEB_LIBRARY_PATH).'fckeditor/editor/fckdialogframe.html \'>";
	}

	function check_for_title()
	{
		if(temp==true){
			// This functions shows that you can interact directly with the editor area
			// DOM. In this way you have the freedom to do anything you want with it.
	
			// Get the editor instance that we want to interact with.
			var oEditor = FCKeditorAPI.GetInstance(\'content\') ;
	
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
			
		// comment see FS#3335
		//	if(document.getElementById(\'title_edited\').value == "false")
		//	{
		//		document.getElementById(\'filename\').value = bestandsnaamNieuw;
		//		if(use_document_title){
		//			document.getElementById(\'title\').value = bestandsnaamNieuw;
		//		}
		//	}
		
		}
		temp=true;
	}

	function trim(s)
	{
	 while(s.substring(0,1) == \' \') {
	  s = s.substring(1,s.length);
	 }
	 while(s.substring(s.length-1,s.length) == \' \') {
	  s = s.substring(0,s.length-1);
	 }
	 return s;
	}

	function check_if_still_empty()
	{
		if(trim(document.getElementById(\'filename\').value) != "")
		{
			document.getElementById(\'title_edited\').value = "true";
		}
	}

</script>';

include (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
include (api_get_path(LIBRARY_PATH).'document.lib.php');
include (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
include (api_get_path(LIBRARY_PATH).'events.lib.inc.php');
include (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
$nameTools = get_lang('CreateDocument');

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '950';
$fck_attribute['ToolbarSet'] = 'Full';
$fck_attribute['Config']['FullPage'] = true;

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$dir = isset($_GET['dir']) ? $_GET['dir'] : $_POST['dir']; // please do not modify this dirname formatting
/*
==============================================================================
		MAIN CODE
==============================================================================
*/

if (api_is_in_group())
{
	$group_properties = GroupManager::get_group_properties($_SESSION['_gid']);
}

if (strstr($dir, '..'))
{
	$dir = '/';
}

if ($dir[0] == '.')
{
	$dir = substr($dir, 1);
}

if ($dir[0] != '/')
{
	$dir = '/'.$dir;
}

if ($dir[strlen($dir) - 1] != '/')
{
	$dir .= '/';
}

// Configuration for the FCKEDITOR
$doc_tree= explode('/', $dir);
$count_dir = count($doc_tree) -2; // "2" because at the begin and end there are 2 "/"
// Level correction for group documents.
if (!empty($group_properties['directory']))
{
	$count_dir = $count_dir > 0 ? $count_dir - 1 : 0;
}
$relative_url='';

for($i=0;$i<($count_dir);$i++)
{
	$relative_url.='../';	
}

// we do this in order to avoid the condition in html_editor.php ==> if ($this -> fck_editor->Config['CreateDocumentWebDir']=='' || $this -> fck_editor->Config['CreateDocumentDir']== '')
if ($relative_url== '')
{
	$relative_url = '/';
}
$fck_attribute['Config']['InDocument'] = true;
$fck_attribute['Config']['CreateDocumentDir'] = $relative_url;
if (empty($group_properties['directory']))
{
	$fck_attribute['Config']['CreateDocumentWebDir'] = api_get_path('WEB_COURSE_PATH').$_course['path'].'/document/';
}
else
{
	$fck_attribute['Config']['CreateDocumentWebDir'] = api_get_path('WEB_COURSE_PATH').api_get_course_path().'/document'.$group_properties['directory'].'/';
}
$fck_attribute['Config']['BaseHref'] = api_get_path('WEB_COURSE_PATH').$_course['path'].'/document'.$dir;

$filepath = api_get_path('SYS_COURSE_PATH').$_course['path'].'/document'.$dir;

if (!is_dir($filepath))
{
	$filepath = api_get_path('SYS_COURSE_PATH').$_course['path'].'/document/';
	$dir = '/';
}


//------------

/**************************************************/
$to_group_id = 0;

if (isset ($_SESSION['_gid']) && $_SESSION['_gid'] != '')
{
	$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	$interbreadcrumb[] = array ("url" => "../group/group_space.php?gidReq=".$_SESSION['_gid'], "name" => get_lang('GroupSpace'));
	$noPHP_SELF = true;
	$to_group_id = $_SESSION['_gid'];
	$group = GroupManager :: get_group_properties($to_group_id);
	$path = explode('/', $dir);
	if ('/'.$path[1] != $group['directory'])
	{
		api_not_allowed(true);
	}	
}
$interbreadcrumb[] = array ("url" => "./document.php?curdirpath=".urlencode($_GET['dir']).$req_gid, "name" => get_lang('Documents'));

if (!$is_allowed_in_course)
	api_not_allowed(true);

$is_allowedToEdit = api_is_allowed_to_edit();
if (!($is_allowedToEdit || $_SESSION['group_member_with_upload_rights']))
{
	api_not_allowed(true);
}
/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/

event_access_tool(TOOL_DOCUMENT);
$display_dir = $dir;
if (isset ($group))
{
	$display_dir = explode('/', $dir);
	unset ($display_dir[0]);
	unset ($display_dir[1]);
	$display_dir = implode('/', $display_dir);
}

// Create a new form
$form = new FormValidator('create_document');
// Hidden element with current directory
$form->addElement('hidden', 'dir');
$default['dir'] = $dir;
// Filename

$form->addElement('hidden','title_edited','false','id="title_edited"');

/**
 * Check if a document width the choosen filename allready exists
 */
function document_exists($filename)
{
	global $filepath;
	$filename = replace_dangerous_char($filename);
	return !file_exists($filepath.$filename.'.html');
}

// Change the default renderer for the filename-field to display the dir and extension
$renderer = & $form->defaultRenderer();
//$filename_template = str_replace('{element}', "<tt>$display_dir</tt> {element} <tt>.html</tt>", $renderer->_elementTemplate);
$filename_template = str_replace('{element}', "{element}", $renderer->_elementTemplate);
$renderer->setElementTemplate($filename_template, 'filename');

// initialize group array
$group = array();
	
// If allowed, add element for document title
if (api_get_setting('use_document_title') == 'true')
{	
	//$group[]= $form->add_textfield('title', get_lang('Title'),true,'class="input_titles" id="title"');
	// replace the 	add_textfield with this	
	$group[]=$form->createElement('text','title',get_lang('Title'),'class="input_titles" id="title"');
	//$form->applyFilter('title','trim');		
	//$form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');		
}
else
{		
	//$form->add_textfield('filename', get_lang('FileName'),true,'class="input_titles" id="filename"  onblur="check_if_still_empty()"');
	// replace the 	add_textfield with this 
	$group[]=$form->createElement('text','filename',get_lang('FileName'),'class="input_titles" id="filename"  onblur="check_if_still_empty()"');		
	$form->applyFilter('filename','trim');	
	//$form->addRule('filename', get_lang('ThisFieldIsRequired'), 'required');				
	//$form->addRule('filename', get_lang('FileExists'), 'callback', 'document_exists');
}

/* Show read-only box only in groups */
if(!empty($_SESSION['_gid']))
{
	//$renderer->setElementTemplate('<div class="row"><div class="label"></div><div class="formw">{element}{label}</div></div>', 'readonly');
	$group[]= $form->createElement('checkbox','readonly','',get_lang('ReadOnly'));
}
// add group to the form
$form->addGroup($group, 'filename_group', get_lang('FileName') ,'&nbsp;&nbsp;&nbsp;', false);
$form->addRule('filename_group', get_lang('ThisFieldIsRequired'), 'required');

if (api_get_setting('use_document_title') == 'true')
{			
	$form->addGroupRule('filename_group', array(
	  'title' => array(
	    array(get_lang('ThisFieldIsRequired'), 'required'),	   
	    array(get_lang('FileExists'),'callback', 'document_exists')
	    )
	));
}
else
{
	$form->addGroupRule('filename_group', array(
	  'filename' => array(	   	    
		array(get_lang('ThisFieldIsRequired'), 'required'),
	    array(get_lang('FileExists'),'callback', 'document_exists')
	    )
	));		
}

//$form->addElement('style_submit_button', 'submit', get_lang('SaveDocument'), 'class="save"');

// HTML-editor
$form->add_html_editor('content','', false, false);
// Comment-field

//$form->addElement('textarea', 'comment', get_lang('Comment'), array ('rows' => 5, 'cols' => 50));
$form->addElement('style_submit_button', 'submit', get_lang('langCreateDoc'), 'class="save"');
$form->setDefaults($default);

// HTML
$form->addElement('html','<div id="frmModel" style="display:block; height:950px;width:20%; position:absolute; top:135px; left:1px;"></div>');

// If form validates -> save the new document
if ($form->validate())
{
	$values = $form->exportValues();
	$readonly = isset($values['readonly']) ? 1 : 0;
	
	$values['title']=addslashes(trim($values['title']));

	$clean_val=$values['filename']; 

	$clean_val=replace_dangerous_char(($clean_val));
	$clean_val=disable_dangerous_file($clean_val);
	$clean_val=replace_accents($clean_val);

	
	$values['filename']=$clean_val;
	
	if (api_get_setting('use_document_title') != 'true')
	{
		$values['title'] = $values['filename'];
	}
	else
	{
		$values['filename'] = $values['title'];	
	}
	
	$filename = replace_accents($values['filename']);
	$texte = $values['content'];
	$title = $values['filename'];
	$extension = 'html';
	if (!strstr($texte, '/css/frames.css'))
	{
		$texte = str_replace('</head>', '<link rel="stylesheet" href="./css/frames.css" type="text/css" /></head>', $texte);
	}
	if ($fp = @ fopen($filepath.$filename.'.'.$extension, 'w'))
	{
		$texte = text_filter($texte);
		
		$content = str_replace(api_get_path('WEB_COURSE_PATH'), $_configuration['url_append'].'/courses/', $texte);
		
		
		// change the path of mp3 to absolute
		// first regexp deals with ../../../ urls
		// Disabled by Ivan Tcholakov.
		//$content = preg_replace("|(flashvars=\"file=)(\.+/)+|","$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/',$content);
		//second regexp deals with audio/ urls
		// Disabled by Ivan Tcholakov.
		//$content = preg_replace("|(flashvars=\"file=)([^/]+)/|","$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/$2/',$content);		 
		 
		fputs($fp, $content);

		fclose($fp);
		
		$files_perm = api_get_setting('permissions_for_new_files');
		$files_perm = octdec(!empty($files_perm)?$files_perm:'0770');
		chmod($filepath.$filename.'.'.$extension,$files_perm);

		$perm = api_get_setting('permissions_for_new_directories');
		$perm = octdec(!empty($perm)?$perm:'0770');
		if (!is_dir($filepath.'css'))
		{
			mkdir($filepath.'css');
			chmod($filepath.'css', $perm);
			$doc_id = add_document($_course, $dir.'css', 'folder', 0, 'css');

			api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', $_user['user_id']);
			api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', $_user['user_id']);
		}

		if (!is_file($filepath.'css/frames.css'))
		{
			//make a copy of the current css for the new document			
			
			copy(api_get_path(SYS_CODE_PATH).'css/'.api_get_setting('stylesheets').'/frames.css', $filepath.'css/frames.css');
			
			$doc_id = add_document($_course, $dir.'css/frames.css', 'file', filesize($filepath.'css/frames.css'), 'frames.css');

			api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id']);
			api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', $_user['user_id']);
		}

		$file_size = filesize($filepath.$filename.'.'.$extension);
		$save_file_path = $dir.$filename.'.'.$extension;

		$document_id = add_document($_course, $save_file_path, 'file', $file_size, $filename,null,$readonly);
		if ($document_id)
		{
			api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentAdded', $_user['user_id'], $to_group_id);

			//update parent folders
			item_property_update_on_folder($_course, $_GET['dir'], $_user['user_id']);

			$new_comment = isset ($_POST['comment']) ? trim($_POST['comment']) : '';
			$new_title = isset ($_POST['title']) ? trim($_POST['title']) : '';
			if ($new_comment || $new_title)
			{
				$TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
				$ct = '';
				if ($new_comment)
					$ct .= ", comment='$new_comment'";
				if ($new_title)
					$ct .= ", title='$new_title'";
				api_sql_query("UPDATE $TABLE_DOCUMENT SET".substr($ct, 1)." WHERE id = '$document_id'", __FILE__, __LINE__);
			}
			$dir= substr($dir,0,-1);
			header('Location: document.php?curdirpath='.urlencode($dir));
			exit ();
		}
	}
	else
	{
		Display :: display_header($nameTools, "Doc");
		//api_display_tool_title($nameTools);
		Display :: display_error_message(get_lang('Impossible'));
		Display :: display_footer();
	}
}
else
{
	Display :: display_header($nameTools, "Doc");
	//api_display_tool_title($nameTools);
	$form->display();
	Display :: display_footer();
}
?>
