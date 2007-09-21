<?php // $Id: configure_homepage.php 13176 2007-09-21 14:28:33Z elixir_julian $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2007 Dokeos S.A.
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

// name of the language file that needs to be included
$language_file = array ('admin', 'accessibility');

$cidReset=true;

include('../inc/global.inc.php');

$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
require_once(api_get_path(LIBRARY_PATH).'WCAG/WCAG_rendering.php');
require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
require_once(api_get_path(LIBRARY_PATH).'fckeditor/fckeditor.php');
require_once(api_get_path(LIBRARY_PATH).'security.lib.php');

$action=Security::remove_XSS($_GET['action']);

$tbl_category=Database::get_main_table(TABLE_MAIN_CATEGORY);

$tool_name=get_lang('ConfigureHomePage');

$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));

if(!empty($action)){
	$interbreadcrumb[]=array('url' => 'configure_homepage.php',"name" => get_lang('ConfigureHomePage'));
	switch($action){
		case "edit_top":
			$tool_name=get_lang("EditHomePage");
			break;
		case "edit_news":
			$tool_name=get_lang("EditNews");
			break;
		case "edit_notice":
			$tool_name=get_lang("EditNotice");
			break;
		case "insert_link":
			$tool_name=get_lang("InsertLink");
			break;
		case "edit_link":
			$tool_name=get_lang("EditLink");
			break;
	}
}

//The global logic for language priorities should be: 
//- take language selected when connecting ($_SESSION['user_language_choice']) 
//  or last language selected (taken from select box into SESSION by global.inc.php) 
//  or, if unavailable;
//- take default user language ($_SESSION['_user']['language']) - which is taken from 
//  the database in local.inc.php or, if unavailable;
//- take platform language (taken from the database campus setting 'platformLanguage')
// Then if a language file doesn't exist, it should be created. 
// The default language for the homepage should use the default platform language 
// (if nothing else is selected), which means the 'no-language' file should be taken
// to fill a new 'language-specified' language file, and then only the latter should be
// modified. The original 'no-language' files should never be modified.

// ----- Language selection -----
// The final language selected and used everywhere in this script follows the rules
// described above and is put into "$lang". Because this script includes 
// global.inc.php, the variables used for language purposes below are considered safe.

$lang = ''; //el for "Edit Language"
if(!empty($_SESSION['user_language_choice']))
{
	$lang=$_SESSION['user_language_choice'];
}
elseif(!empty($_SESSION['_user']['language']))
{
	$lang=$_SESSION['_user']['language'];
}
else
{
	$lang=get_setting('platformLanguage');
}

// ----- Ensuring availability of main files in the corresponding language -----
$homep = '../../home/'; //homep for Home Path
$menuf = 'home_menu'; //menuf for Menu File
$newsf = 'home_news'; //newsf for News File
$topf = 'home_top'; //topf for Top File
$noticef = 'home_notice'; //noticef for Notice File
$ext = '.html'; //ext for HTML Extension - when used frequently, variables are 
				// faster than hardcoded strings
$homef = array($menuf,$newsf,$topf,$noticef);

// If language-specific file does not exist, create it by copying default file
foreach($homef as $my_file)
{
	if(!file_exists($homep.$my_file.'_'.$lang.$ext))
	{
		copy($homep.$my_file.$ext,$homep.$my_file.'_'.$lang.$ext);
	}
}

// Check WCAG settings and prepare edition using WCAG
$errorMsg='';
if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
	$errorMsg=WCAG_Rendering::request_validation();
}

// Filter link param
$link = '';
if(!empty($_GET['link']))
{
	$link=$_GET['link'];
	// If the link parameter is suspicious, empty it
	if(strstr($link,'/') || !strstr($link,'.html') || strstr($link,'\\'))
	{
		$link='';
		$action='';
	}
}

// Start analysing requested actions
if(!empty($action))
{
	if($_POST['formSent'])
	{
		//variables used are $homep for home path, $menuf for menu file, $newsf
		// for news file, $topf for top file, $noticef for noticefile, 
		// $ext for '.html'
		switch($action)
		{
			case 'edit_top':
				// Filter
				$home_top='';
				if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
					$home_top=WCAG_Rendering::prepareXHTML();
				} else {
					$home_top=trim(stripslashes($_POST['home_top']));
				}
				// Write
				if(file_exists($homep.$topf.'_'.$lang.$ext))
				{
					if(is_writable($homep.$topf.'_'.$lang.$ext))
					{
						$fp=fopen($homep.$topf.'_'.$lang.$ext,"w");
						fputs($fp,$home_top);
						fclose($fp);
					}
					else
					{
						$errorMsg=get_lang('HomePageFilesNotWritable');
					}
				}
				else //File does not exist
				{
					$fp=fopen($homep.$topf.'_'.$lang.$ext,"w");
					fputs($fp,$home_top);
					fclose($fp);
				}
				break;
			case 'edit_notice':
				// Filter
				$notice_title=trim(strip_tags(stripslashes($_POST['notice_title'])));
				$notice_text=trim(str_replace(array("\r","\n"),array("","<br />"),strip_tags(stripslashes($_POST['notice_text']),'<a>')));	
				/*if(empty($notice_title))
				{
					$errorMsg=get_lang('PleaseEnterNoticeTitle');
				}
				elseif(empty($notice_text))
				{
					$errorMsg=get_lang('PleaseEnterNoticeText');
				}*/
				if(empty($notice_title) || empty($notice_text)){
					$errorMsg=get_lang('NoticeWillBeNotDisplayed');
				}
				// Write
				if(file_exists($homep.$noticef.'_'.$lang.$ext))
				{
					if(is_writable($homep.$noticef.'_'.$lang.$ext))
					{
						$fp=fopen($homep.$noticef.'_'.$lang.$ext,"w");
						if($errorMsg==''){
							fputs($fp,"<b>$notice_title</b><br />\n$notice_text");
						}
						else{
							fputs($fp,"");
						}
						fclose($fp);
					}
					else
					{
						$errorMsg.="<br/>\n".get_lang('HomePageFilesNotWritable');
					}
				}
				else //File does not exist
				{
					$fp=fopen($homep.$noticef.'_'.$lang.$ext,"w");
					fputs($fp,"<b>$notice_title</b><br />\n$notice_text");
					fclose($fp);
				}				
				break;
			case 'edit_news':
				//Filter
				//$s_languages_news=$_POST["news_languages"];
				if (api_get_setting('wcag_anysurfer_public_pages')=='true')
				{
					$home_news=WCAG_rendering::prepareXHTML();
				} else
				{ 
					$home_news=trim(stripslashes($_POST['home_news']));
				}
				//Write
				if($s_languages_news!="all"){
	
					if(file_exists($homep.$newsf.'_'.$s_languages_news.$ext)){
						if(is_writable($homep.$newsf.'_'.$s_languages_news.$ext)){
							$fp=fopen($homep.$newsf.'_'.$s_languages_news.$ext,"w");
							fputs($fp,$home_news);
							fclose($fp);
						}
						else{
							$errorMsg=get_lang('HomePageFilesNotWritable');
						}
					}
					//File not exists
					else{
						$fp=fopen($homep.$newsf.'_'.$s_languages_news.$ext,"w");
						fputs($fp,$home_news);
						fclose($fp);
					}
				}
				else //we update all the news file
				{
					$_languages=api_get_languages();
	
					foreach($_languages["name"] as $key => $value){
	
						$english_name=$_languages["folder"][$key];
	
						if(file_exists($homep.$newsf.'_'.$english_name.$ext)){
							if(is_writable($homep.$newsf.'_'.$english_name.$ext)){
								$fp=fopen($homep.$newsf.'_'.$english_name.$ext,"w");
								fputs($fp,$home_news);
								fclose($fp);
							}
							else{
								$errorMsg=get_lang('HomePageFilesNotWritable');
							}
						}
						//File not exists
						else{
							$fp=fopen($homep.$newsf.'_'.$english_name.$ext,"w");
							fputs($fp,$home_news);
							fclose($fp);
						}
					}
				}
				break;
			case 'insert_link':
			case 'edit_link':
				$link_index=intval($_POST['link_index']);
				$insert_where=intval($_POST['insert_where']);
				$link_name=trim(stripslashes($_POST['link_name']));
				$link_url=trim(stripslashes($_POST['link_url']));
				// WCAG
				if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
					$link_html=WCAG_Rendering::prepareXHTML();
				} else {
					$link_html=trim(stripslashes($_POST['link_html']));
				}
				$filename=trim(stripslashes($_POST['filename']));
				$target_blank=$_POST['target_blank']?true:false;
	
				if($link_url == 'http://')
				{
					$link_url='';
				}
				elseif(!empty($link_url) && !strstr($link_url,'://'))
				{
					$link_url='http://'.$link_url;
				}
	
				if(!is_writable($homep.$menuf.'_'.$lang.$ext))
				{
					$errorMsg=get_lang('HomePageFilesNotWritable');
				}
				elseif(empty($link_name))
				{
					$errorMsg=get_lang('PleaseEnterLinkName');
				}
				else
				{
					// New links are added as new files in the home/ directory
					if($action == 'insert_link' || empty($filename) || strstr($filename,'/') || !strstr($filename,'.html'))
					{
						$filename=replace_dangerous_char($link_name,'strict').'.html';
					}
					// "home_" prefix for links are renamed to "user_" prefix (to avoid name clash with existing home page files)
					if(!empty($filename))
					{
						$filename=str_replace('home_','user_',$filename);
					}
					// If the typical language suffix is not found in the file name,
					// replace the ".html" suffix by "_en.html" or the active menu language
					if(!strstr($filename,'_'.$lang.$ext))
					{
						$filename=str_replace($ext,'_'.$lang.$ext,$filename);
					}
					// Get the contents of home_menu_en.html (or active menu language 
					// version) into $home_menu as an array of one entry per line
					$home_menu=file($homep.$menuf.'_'.$lang.$ext);
					// Prepare place to insert the new link into (default is end of file) 
					if($insert_where < -1 || $insert_where > (sizeof($home_menu) - 1))
					{
						$insert_where=sizeof($home_menu) - 1;
					}
					// For each line of the file, remove trailing spaces and special chars
					foreach($home_menu as $key=>$enreg)
					{
						$home_menu[$key]=trim($enreg);
					}
					// If the given link url is empty, then replace the link url by a link to the link file created 
					if(empty($link_url))
					{
						$link_url=api_get_path(WEB_PATH).'index.php?include='.urlencode($filename);
						// If the file doesn't exist, then create it and
						// fill it with default text	
						if(!file_exists(api_get_path(SYS_PATH).'home/'.$filename))
						{
							$fp=@fopen(api_get_path(SYS_PATH).'home/'.$filename,'w');
	
							if($fp)
							{
								fputs($fp,get_lang('MyTextHere'));
	
								fclose($fp);
							}
						}
					}
					// If the requested action is to edit a link, open the file and
					// write to it (if the file doesn't exist, create it)
					if($action == 'edit_link' && !empty($link_html))
					{
						$fp=@fopen(api_get_path(SYS_PATH).'home/'.$filename,'w');
	
						if($fp)
						{
							fputs($fp,$link_html);
	
							fclose($fp);
						}
					}
					// If the requested action is to create a link, make some room
					// for the new link in the home_menu array at the requested place
					// and insert the new link there
					if($action == 'insert_link')
					{
						for($i=sizeof($home_menu);$i;$i--)
						{
							if($i > $insert_where)
							{
								$home_menu[$i]=$home_menu[$i-1];
							}
							else
							{
								break;
							}
						}
	
						$home_menu[$insert_where+1]='<li><a href="'.$link_url.'" target="'.($target_blank?'_blank':'_self').'">'.$link_name.'</a></li>';
					}
					else // If the request is about a link edition, change the link 
					{
						$home_menu[$link_index]='<li><a href="'.$link_url.'" target="'.($target_blank?'_blank':'_self').'">'.$link_name.'</a></li>';
					}
					// Re-build the file from the home_menu array
					$home_menu=implode("\n",$home_menu);
					// Write
					if(file_exists($homep.$menuf.'_'.$lang.$ext))
					{
						if(is_writable($homep.$menuf.'_'.$lang.$ext))
						{
							$fp=fopen($homep.$menuf.'_'.$lang.$ext,"w");
							fputs($fp,$home_menu);
							fclose($fp);
						}
						else
						{
							$errorMsg=get_lang('HomePageFilesNotWritable');
						}
					}
					else //File does not exist
					{
						$fp=fopen($homep.$menuf.'_'.$lang.$ext,"w");
						fputs($fp,$home_menu);
						fclose($fp);
					}				
				}
				break;
		} //end of switch($action)

		if(empty($errorMsg))
		{
			header('Location: '.api_get_self());
			exit();
		}
	}
	else //if POST[formSent] is not set
	{
		switch($action)
		{
			case 'open_link':
				// Previously, filtering of GET['link'] was done here but it left
				// a security threat. Filtering has now been moved outside conditions
				break;
			case 'delete_link':
				// A link is deleted by getting the file into an array, removing the
				// link and re-writing the array to the file
				$link_index=intval($_GET['link_index']);
		
				$home_menu=file($homep.$menuf.'_'.$lang.$ext);
		
				foreach($home_menu as $key=>$enreg)
				{
					if($key == $link_index)
					{
						unset($home_menu[$key]);
					}
					else
					{
						$home_menu[$key]=trim($enreg);
					}
				}
		
				$home_menu=implode("\n",$home_menu);
		
				$fp=fopen($homep.$menuf.'_'.$lang.$ext,'w');
		
				fputs($fp,$home_menu);
		
				fclose($fp);
		
				header('Location: '.api_get_self());
				exit();
				break;
			case 'edit_top':
				// This request is only the preparation for the update of the home_top
				$home_top = '';
				if(is_file($homep.$topf.'_'.$lang.$ext) 
					&& is_readable($homep.$topf.'_'.$lang.$ext))
				{
					$home_top=file_get_contents($homep.$topf.'_'.$lang.$ext);				
				}
				elseif(is_file($homep.$topf.$lang.$ext)
					&& is_readable($homep.$topf.$lang.$ext))
				{
					$home_top=file_get_contents($homep.$topf.$lang.$ext);				
				}
				else
				{
					$errorMsg=get_lang('HomePageFilesNotReadable');					
				}
				break;
			case 'edit_notice':
				// This request is only the preparation for the update of the home_notice
				$home_notice = '';
				if(is_file($homep.$noticef.'_'.$lang.$ext) 
					&& is_readable($homep.$noticef.'_'.$lang.$ext))
				{
					$home_notice=file($homep.$noticef.'_'.$lang.$ext);				
				}
				elseif(is_file($homep.$noticef.$lang.$ext)
					&& is_readable($homep.$noticef.$lang.$ext))
				{
					$home_notice=file($homep.$noticef.$lang.$ext);				
				}
				else
				{
					$errorMsg=get_lang('HomePageFilesNotReadable');					
				}		
				$notice_title=strip_tags($home_notice[0]);
				$notice_text=strip_tags(str_replace('<br />',"\n",$home_notice[1]),'<a>');
				break;
			case 'edit_news':
				// This request is the preparation for the update of the home_news page
				$home_news = '';
				if(is_file($homep.$newsf.'_'.$lang.$ext) 
					&& is_readable($homep.$newsf.'_'.$lang.$ext))
				{
					$home_news=file_get_contents($homep.$newsf.'_'.$lang.$ext);				
		//			$home_news=file($homep.$newsf.$ext);
		//			$home_news=implode('',$home_news);
				}
				elseif(is_file($homep.$newsf.$lang.$ext)
					&& is_readable($homep.$newsf.$lang.$ext))
				{
					$home_news=file_get_contents($homep.$newsf.$lang.$ext);				
				}
				else
				{
					$errorMsg=get_lang('HomePageFilesNotReadable');					
				}		
				break;
			case 'insert_link':	
				// This request is the preparation for the addition of an item in home_menu
				$home_menu = '';
				if(is_file($homep.$menuf.'_'.$lang.$ext) 
					&& is_readable($homep.$menuf.'_'.$lang.$ext))
				{
					$home_menu=file($homep.$menuf.'_'.$lang.$ext);				
				}
				elseif(is_file($homep.$menuf.$lang.$ext)
					&& is_readable($homep.$menuf.$lang.$ext))
				{
					$home_menu=file($homep.$menuf.$lang.$ext);				
				}
				else
				{
					$errorMsg=get_lang('HomePageFilesNotReadable');					
				}
				break;
			case 'edit_link':
				// This request is the preparation for the edition of the links array
				$home_menu = '';
				if(is_file($homep.$menuf.'_'.$lang.$ext) 
					&& is_readable($homep.$menuf.'_'.$lang.$ext))
				{
					$home_menu=file($homep.$menuf.'_'.$lang.$ext);				
				}
				elseif(is_file($homep.$menuf.$lang.$ext)
					&& is_readable($homep.$menuf.$lang.$ext))
				{
					$home_menu=file($homep.$menuf.$lang.$ext);				
				}
				else
				{
					$errorMsg=get_lang('HomePageFilesNotReadable');					
				}

				$link_index=intval($_GET['link_index']);		
				$target_blank=false;
				$link_name='';
				$link_url='';
		
				// For each line of the home_menu file
				foreach($home_menu as $key=>$enreg)
				{
					// Check if the current item is the one we want to update
					if($key == $link_index)
					{
						// This is the link we want to update
						// Check if the target should be "_blank"
						if(strstr($enreg,'target="_blank"'))
						{
							$target_blank=true;
						}
						// Remove dangerous HTML tags from the link itself (this is an
						// additional measure in case a link previously contained
						// unsecure tags)
						$link_name=strip_tags($enreg);
						
						// Get the contents of "href" attribute in $link_url
						$enreg=explode('href="',$enreg);
						list($link_url)=explode('"',$enreg[sizeof($enreg)-1]);
		
						// If the link contains the web root of this portal, then strip
						// it off and keep only the name of the file that needs edition
						if(strstr($link_url,$_configuration['root_web']) && strstr($link_url,'?include='))
						{
							$link_url=explode('?include=',$link_url);
		
							$filename=$link_url[sizeof($link_url)-1];
		
							if(!strstr($filename,'/') && strstr($filename,'.html'))
							{
								// Get oonly the contents of the link file					
								$link_html=file(api_get_path(SYS_PATH).'home/'.$filename);
								$link_html=implode('',$link_html);
								$link_url='';
							}
							else
							{
								$filename='';
							}
						}
						break;
					}
				}
				break;
		}//end of second switch($action) (when POST['formSent'] was not set, yet)
	}// end of "else" in if($_POST['formSent']) condition
}
else //if $action is empty, then prepare a list of the course categories to display (?)
{
	$result=api_sql_query("SELECT name FROM $tbl_category WHERE parent_id IS NULL ORDER BY tree_pos",__FILE__,__LINE__);
	$Categories=api_store_result($result);
}

// -------------------------
// ---- Display section ----
// -------------------------

Display::display_header($tool_name);

//api_display_tool_title($tool_name);

switch($action){
	case 'open_link':
		if(!empty($link))
		{
			// $link is only set in case of action=open_link and is filtered
			include($homep.$link);
		}
		break;
	case 'edit_notice':
		//------------  Display for edit_notice case --------------
		?>
		<form action="<?php echo api_get_self(); ?>?action=<?php echo $action; ?>" method="post" style="margin:0px;">
		<input type="hidden" name="formSent" value="1"/>
		
		<?php
		if(!empty($errorMsg))
		{
			//echo '<tr><td colspan="2">';
			Display::display_normal_message($errorMsg);
			//echo '</td></tr>';
		}
		
		?>
		<table border="0" cellpadding="5" cellspacing="0">
		<tr><td colspan="2"><?php echo '<span style="font-style: italic;">'.get_lang('LetThoseFieldsEmptyToHideTheNotice').'</span>'; ?></tr>
		<tr>
		  <td nowrap="nowrap"><?php echo get_lang('NoticeTitle'); ?> :</td>
		  <td><input type="text" name="notice_title" size="30" maxlength="50" value="<?php echo htmlentities($notice_title); ?>" style="width: 350px;"/></td>
		</tr>
		<tr>
		  <td nowrap="nowrap" valign="top"><?php echo get_lang('NoticeText'); ?> :</td>
		  <td><textarea name="notice_text" cols="30" rows="5" wrap="virtual" style="width: 350px;"><?php echo htmlentities($notice_text); ?></textarea></td>
		</tr>
		<tr>
		  <td>&nbsp;</td>
		  <td><input type="submit" value="<?php echo get_lang('Ok'); ?>"/></td>
		</tr>
		</table>
		</form>		
		<?php
		break;
	case 'insert_link':
	case 'edit_link':
		?>
		<form action="<?php echo api_get_self(); ?>?action=<?php echo $action; ?>" method="post" style="margin:0px;">
		<input type="hidden" name="formSent" value="1"/>
		<input type="hidden" name="link_index" value="<?php if($action == 'edit_link') echo $link_index; else echo '0'; ?>"/>
		<input type="hidden" name="filename" value="<?php if($action == 'edit_link') echo $filename; else echo ''; ?>"/>
		
		<table border="0" cellpadding="5" cellspacing="0">
		<?php
		if(!empty($errorMsg))
		{
			echo '<tr><td colspan="2">';
			Display::display_normal_message($errorMsg); //main API
			echo '</td></tr>';
		}
		?>		
		<tr>
		  <td nowrap="nowrap"><?php echo get_lang('LinkName'); ?> :</td>
		  <td><input type="text" name="link_name" size="30" maxlength="50" value="<?php echo htmlentities($link_name); ?>" style="width: 350px;"/></td>
		</tr>
		<tr>
		  <td nowrap="nowrap"><?php echo get_lang('LinkURL'); ?> (<?php echo get_lang('Optional'); ?>) :</td>
		  <td><input type="text" name="link_url" size="30" maxlength="100" value="<?php if(empty($link_url)) echo 'http://'; else echo htmlentities($link_url); ?>" style="width: 350px;"/></td>
		</tr>
		
		<?php 
		if($action == 'insert_link') 
		{
		?>
			<tr>
			  <td nowrap="nowrap"><?php echo get_lang('InsertThisLink'); ?> :</td>
			  <td><select name="insert_where">
			  <option value="-1"><?php echo get_lang('FirstPlace'); ?></option>
			
			<?php
			foreach($home_menu as $key=>$enreg)
			{
			?>
			
			  <option value="<?php echo $key; ?>" <?php if($formSent && $insert_where == $key) echo 'selected="selected"'; ?> ><?php echo get_lang('After'); ?> &quot;<?php echo trim(strip_tags($enreg)); ?>&quot;</option>
			
			<?php
			}
			?>
			
			  </select></td>
			</tr>
		<?php 
		}
		?>
		
		<tr>
		  <td nowrap="nowrap"><?php echo get_lang('OpenInNewWindow'); ?> ?</td>
		  <td><input class="checkbox" type="checkbox" name="target_blank" value="1" <?php if($target_blank) echo 'checked="checked"'; ?> /> <?php echo get_lang('Yes'); ?></td>
		</tr>
		
		<?php 
		if($action == 'edit_link' && empty($link_url))
		{
		?>
			</table>
			<table border="0" cellpadding="5" cellspacing="0" width="100%">
			<tr>
			  <td>
			
			<?php
			    //api_disp_html_area('link_html',isset($_POST['link_html'])?$_POST['link_html']:$link_html,'400px');
				if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
					echo WCAG_Rendering::create_xhtml(isset($_POST['link_html'])?$_POST['link_html']:$link_html);
				} else {
					$oFCKeditor = new FCKeditor('link_html') ;
					$oFCKeditor->BasePath	= api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/' ;
					$oFCKeditor->Height		= '400';
					$oFCKeditor->Width		= '100%';
					$oFCKeditor->Value		= isset($_POST['link_html'])?$_POST['link_html']:$link_html;
					$oFCKeditor->Config['CustomConfigurationsPath'] = api_get_path(REL_PATH)."main/inc/lib/fckeditor/myconfig.js";
					$oFCKeditor->ToolbarSet = "Small";
				
					$TBL_LANGUAGES = Database::get_main_table(TABLE_MAIN_LANGUAGE);
					$sql="SELECT isocode FROM ".$TBL_LANGUAGES." WHERE english_name='".$_SESSION["_user"]["language"]."'";
					$result_sql=api_sql_query($sql);
					$isocode_language=mysql_result($result_sql,0,0);
					$oFCKeditor->Config['DefaultLanguage'] = $isocode_language;
					echo $oFCKeditor->CreateHtml();
				}
				
			?>
			
			  </td>
			</tr>
			<tr>
			  <td><input type="submit" value="<?php echo get_lang('Ok'); ?>"/></td>
			</tr>
		<?php
		} 
		else
		{
			echo '<tr>' .
					'<td>&nbsp;</td>' .
					'<td><input type="submit" value="'.get_lang('Ok').'"/></td>' .
				'</tr>';
		}
		?>
		
		</table>
		</form>
		
		<?php
		break;
	case 'edit_top':
	case 'edit_news':
		if($action == 'edit_top')
		{
			$name= $topf;
			$open = $home_top;
		}
		else
		{
			$name = $newsf;
			$open=file_get_contents($homep.$newsf.'_'.$lang.$ext);
	
		}
		// print form header + important formSent attribute
		echo '<form action="'.api_get_self().'?action='.$action.'" method="post" style="margin:0px;">';
		echo '<input type="hidden" name="formSent" value="1"/>';

		if(!empty($errorMsg))
		{
			Display::display_normal_message($errorMsg); //main API
		}
		
		if($action == 'edit_news'){
			$_languages=api_get_languages();
			echo get_lang("ChooseNewsLanguage")." : <select name='news_languages'><option value='all'>".get_lang("AllLanguages")."</option>";
			foreach($_languages["name"] as $key => $value){
				$english_name=$_languages["folder"][$key];
				if($language==$english_name){
					echo "<option value='$english_name' selected=selected>$value</option>";
				}
				else{
					echo "<option value='$english_name'>$value</option>";
				}
			}
			echo "</select>";
		}
		?>
		
		<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr>
		  <td>
		
		<?php
		if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
			// Print WCAG-specific HTML editor
			echo '<script type="text/javascript" src="'.api_get_path(REL_PATH).'main/inc/lib/fckeditor/editor/plugins/ImageManagerStandalone/generic_dialog_common.js'.'" />';
			echo WCAG_Rendering::create_xhtml($open);
			
		} else {
			$open=str_replace('{rel_path}',api_get_path(REL_PATH),$open);
			$oFCKeditor = new FCKeditor($name) ;
			$oFCKeditor->BasePath	= api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/' ;
			$oFCKeditor->Height		= '400';
			$oFCKeditor->Width		= '100%';
			$oFCKeditor->Value		= $open;
			$oFCKeditor->Config['CustomConfigurationsPath'] = api_get_path(REL_PATH)."main/inc/lib/fckeditor/myconfig.js";
			$oFCKeditor->ToolbarSet = "Small";
		
			$TBL_LANGUAGES = Database::get_main_table(TABLE_MAIN_LANGUAGE);
			$sql="SELECT isocode FROM ".$TBL_LANGUAGES." WHERE english_name='".$_SESSION["_user"]["language"]."'";
			$result_sql=api_sql_query($sql);
			$isocode_language=mysql_result($result_sql,0,0);
			$oFCKeditor->Config['DefaultLanguage'] = $isocode_language;
			echo $oFCKeditor->CreateHtml();
		}
		?>
		  </td>
		</tr>
		<tr>
		  <td><input type="submit" value="<?php echo get_lang('Ok'); ?>"/></td>
		</tr>
		</table>
		</form>
		
		<?php
		break;
	default: // When no action applies, default page to update campus homepage
		?>		
		<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr>
		  <td width="80%" colspan="2">
			<a href="<?php echo api_get_self(); ?>?action=edit_top"><img src="../img/edit.gif" border="0" title="<?php echo htmlentities(get_lang('Modify')); ?>"/></a> <a href="<?php echo api_get_self(); ?>?action=edit_top"><?php echo get_lang('EditHomePage'); ?></a>
		  </td>
		  <td width="20%">
			<a href="<?php echo api_get_self(); ?>?action=insert_link"><img src="../img/insert_row.png" border="0"/></a> <a href="<?php echo api_get_self(); ?>?action=insert_link"/><?php echo get_lang('InsertLink'); ?></a>
		  </td>
		</tr>
		<tr>
		  <td width="80%" colspan="2" valign="top">
			<table border="0" cellpadding="5" cellspacing="0" width="100%">
			<tr>
			  <td colspan="2">
				<?php
					//print home_top contents
					if(file_exists($homep.$topf.'_'.$lang.$ext))
					{
						$home_top_temp=file_get_contents($homep.$topf.'_'.$lang.$ext);
					}
					else
					{
						$home_top_temp=file_get_contents($homep.$topf.$ext);					
					}
					$open=str_replace('{rel_path}',api_get_path(REL_PATH),$home_top_temp);
					echo $open;
				?>		
			  </td>
			</tr>
			<tr>
			  <td width="50%">
				<br />
				<a href="course_category.php"><img src="../img/edit.gif" border="0" title="<?php echo htmlentities(get_lang('Modify')); ?>"/></a> <a href="course_category.php"><?php echo get_lang('EditCategories'); ?></a>
			  </td>
			  <td width="50%">
				<br />
				<!--<a href="<?php echo api_get_self(); ?>?action=edit_news"><img src="../img/edit.gif" border="0" title="<?php echo htmlentities(get_lang('Modify')); ?>"/></a> <a href="<?php echo api_get_self(); ?>?action=edit_news"><?php echo get_lang('EditNews'); ?></a>-->
			  </td>
			</tr>
			<tr>
			  <td width="50%" valign="top">
				<table border="0" cellpadding="5" cellspacing="0" width="100%">
				<?php
				if(sizeof($Categories))
				{
					foreach($Categories as $enreg)
					{
						echo '<tr><td><img src="../img/opendir.gif" border="0" alt="'.get_lang('Directory').'"/>&nbsp;'.$enreg['name'].'</td></tr>';
					}		
					unset($Categories);
				}
				else
				{
					echo get_lang('NoCategories');
				}
				?>
		
				</table>
			  </td>
			  <!--<td width="50%" valign="top">		
				<?php
		
				if(file_exists($homep.$newsf.'_'.$lang.$ext))
				{
					include ($homep.$newsf.'_'.$lang.$ext);
				}
				else
				{
					include ($homep.$newsf.$ext);
				}
			?>
		
			  </td>-->
			</tr>
			</table>
		  </td>
		  <td width="20%" rowspan="3" valign="top">
			<div class="menu" style="width: 100%;">
			<?php
			api_display_language_form();
			?>
			<form id="loginform">
				<table cellpadding="0">
					<tr>
						<td><label><?php echo get_lang('LoginName'); ?></label></td>
						<td><input type="text" id="login" size="15" value="" disabled="disabled" /></td>
					</tr>
					<tr>
						<td><label><?php echo get_lang('UserPassword'); ?></label></td>
						<td><input type="password" id="password" size="15" disabled="disabled" /></td>
					</tr>
				</table>
				<input type="button" value="<?php echo get_lang('Ok'); ?>" disabled="disabled" />
			</form>
			<div class="menusection">
				<span class="menusectioncaption"><?php echo get_lang('User'); ?></span>
				<ul class="menulist">
				<li><span style="color: #9D9DA1; font-weight: bold;"><?php echo ucfirst(get_lang('Registration')); ?></span></li>
				<li><span style="color: #9D9DA1; font-weight: bold;"><?php echo ucfirst(get_lang('LostPassword')); ?></span></li>
				</ul>
			</div>
			<div class="menusection">
				<span class="menusectioncaption"><?php echo ucfirst(get_lang('General')); ?></span>
				<ul class="menulist">
		
				<?php
					$home_menu = '';
					if(file_exists($homep.$menuf.'_'.$lang.$ext))
					{
						$home_menu = file($homep.$menuf.'_'.$lang.$ext);
					}
					else
					{
						$home_menu = file ($homep.$menuf.$ext);
					}
				
					foreach($home_menu as $key=>$enreg)
					{
						$enreg=trim($enreg);
				
						if(!empty($enreg))
						{
							$edit_link='<a href="'.api_get_self().'?action=edit_link&amp;link_index='.$key.'"><img src="../img/edit.gif" border="0" style="margin-top: 2px;" title="'.htmlentities(get_lang('Modify')).'"/></a>';
							$delete_link='<a href="'.api_get_self().'?action=delete_link&amp;link_index='.$key.'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang('ConfirmYourChoice'))).'\')) return false;"><img src="../img/delete.gif" border="0" style="margin-top: 2px;" title="'.htmlentities(get_lang('Delete')).'"/></a>';
				
							echo str_replace(array('href="'.api_get_path(WEB_PATH).'index.php?include=','</li>'),array('href="'.api_get_path(WEB_CODE_PATH).'admin/'.basename(api_get_self()).'?action=open_link&link=','<br />'.$edit_link.' '.$delete_link.'</li>'),$enreg);
						}
					}
				?>
		
				</ul>
			</div>
		
			<br />
			&nbsp;&nbsp;<a href="<?php echo api_get_self(); ?>?action=edit_notice"><img src="../img/edit.gif" border="0" title="<?php echo htmlentities(get_lang('Modify')); ?>"/></a> <a href="<?php echo api_get_self(); ?>?action=edit_notice"><?php echo get_lang('EditNotice'); ?></a>
		
			<div class="note">
		
			<?php
			$home_notice = '';
			if(file_exists($homep.$noticef.'_'.$lang.$ext))
			{
				$home_notice = file_get_contents($homep.$noticef.'_'.$lang.$ext);
			}
			else
			{
				$home_notice = file_get_contents($homep.$noticef.$ext);
			}
			echo $home_notice
			?>
		
			</div>
			</div>
		  </td>
		</tr>
		</table>
		
		<?php
		break;
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
