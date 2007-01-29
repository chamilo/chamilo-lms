<?php // $Id: configure_homepage.php 10975 2007-01-29 21:54:57Z pvandermaesen $
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

// name of the language file that needs to be included
$language_file = array ('admin', 'accessibility');

$cidReset=true;

include('../inc/global.inc.php');

$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
include_once(api_get_path(LIBRARY_PATH).'WCAG/WCAG_rendering.php');

include_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');

require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");

$action=$_GET['action'];

$tbl_category=Database::get_main_table(TABLE_MAIN_CATEGORY);

$tool_name=get_lang('ConfigureHomePage');

$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));

$menu_language=$_SESSION['user_language_choice'];

if(!isset($menu_language))
{
	$menu_language=$platformLanguage;
}

if(!file_exists('../../home/home_menu_'.$menu_language.'.html'))
{
	copy('../../home/home_menu.html','../../home/home_menu_'.$menu_language.'.html');
}

$errorMsg='';

if(!empty($action))
{
	if($_POST['formSent'])
	{
		if($action == 'edit_top')
		
		{
			
			$home_top='';
			if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
				$home_top=WCAG_Rendering::prepareXHTML();
			} else {
				$home_top=trim(stripslashes($_POST['home_top']));
			}

			if(!is_writable('../../home/home_top.html'))
			{
				$errorMsg=get_lang('HomePageFilesNotWritable');
			}
			elseif(!empty($home_top))
			{
				$fp=fopen('../../home/home_top.html','w');

				fputs($fp,$home_top);

				fclose($fp);
			}
		}
		elseif($action == 'edit_notice')
		{
			$notice_title=trim(strip_tags(stripslashes($_POST['notice_title'])));
			$notice_text=trim(str_replace(array("\r","\n"),array("","<br />"),strip_tags(stripslashes($_POST['notice_text']),'<a>')));

			if(empty($notice_title))
			{
				$errorMsg=get_lang('PleaseEnterNoticeTitle');
			}
			elseif(empty($notice_text))
			{
				$errorMsg=get_lang('PleaseEnterNoticeText');
			}
			elseif(!is_writable('../../home/home_notice.html'))
			{
				$errorMsg=get_lang('HomePageFilesNotWritable');
			}
			else
			{
				$fp=fopen('../../home/home_notice.html','w');

				fputs($fp,"<b>$notice_title</b><br />\n$notice_text");

				fclose($fp);
			}
		}

		//NEWS
		elseif($action == 'edit_news')
		{

			$s_languages_news=$_POST["news_languages"];
			//echo "langue choisie : ".$s_languages_news;
			$home_news=trim(stripslashes($_POST['home_news']));

			if($s_languages_news!="all"){

				if(file_exists("'../../home/home_news_".$s_languages_news.".html")){
					if(is_writable("../../home/home_news_".$s_languages_news.".html")){
						$fp=fopen("../../home/home_news_".$s_languages_news.".html","w");
						fputs($fp,$home_news);
						fclose($fp);
					}
					else{
						$errorMsg=get_lang('HomePageFilesNotWritable');
					}
				}
				//File not exists
				else{
					$fp=fopen("../../home/home_news_".$s_languages_news.".html","w");
					fputs($fp,$home_news);
					fclose($fp);
				}
			}

			//we update all the news file
			else{
				$_languages=api_get_languages();

				foreach($_languages["name"] as $key => $value){

					$english_name=$_languages["folder"][$key];

					if(file_exists("'../../home/home_news_".$english_name.".html")){
						if(is_writable("../../home/home_news_".$english_name.".html")){
							$fp=fopen("../../home/home_news_".$english_name.".html","w");
							fputs($fp,$home_news);
							fclose($fp);
						}
						else{
							$errorMsg=get_lang('HomePageFilesNotWritable');
						}
					}
					//File not exists
					else{
						$fp=fopen("../../home/home_news_".$english_name.".html","w");
						fputs($fp,$home_news);
						fclose($fp);
					}
				}
			}

			/*if(!is_writable('../../home/home_news.html'))
			{
				$errorMsg=get_lang('HomePageFilesNotWritable');
			}
			elseif(!empty($home_news))
			{
				$fp=fopen('../../home/home_news.html','w');

				fputs($fp,$home_news);

				fclose($fp);
			}*/
		}
		elseif($action == 'insert_link' || $action == 'edit_link')
		{
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

			if(!is_writable('../../home/home_menu_'.$menu_language.'.html'))
			{
				$errorMsg=get_lang('HomePageFilesNotWritable');
			}
			elseif(empty($link_name))
			{
				$errorMsg=get_lang('PleaseEnterLinkName');
			}
			else
			{
				if($action == 'insert_link' || empty($filename) || strstr($filename,'/') || !strstr($filename,'.html'))
				{
					$filename=replace_dangerous_char($link_name,'strict').'.html';
				}

				if(!empty($filename))
				{
					$filename=str_replace('home_','user_',$filename);
				}

				if(!strstr($filename,'_'.$menu_language.'.html'))
				{
					$filename=str_replace('.html','_'.$menu_language.'.html',$filename);
				}

				$home_menu=file('../../home/home_menu_'.$menu_language.'.html');

				if($insert_where < -1 || $insert_where > (sizeof($home_menu) - 1))
				{
					$insert_where=sizeof($home_menu) - 1;
				}

				foreach($home_menu as $key=>$enreg)
				{
					$home_menu[$key]=trim($enreg);
				}

				if(empty($link_url))
				{
					$link_url=$_configuration['root_web'].'index.php?include='.urlencode($filename);

					if(!file_exists($_configuration['root_sys'].'home/'.$filename))
					{
						$fp=@fopen($_configuration['root_sys'].'home/'.$filename,'w');

						if($fp)
						{
							fputs($fp,get_lang('MyTextHere'));

							fclose($fp);
						}
					}
				}

				if($action == 'edit_link' && !empty($link_html))
				{
					$fp=@fopen($_configuration['root_sys'].'home/'.$filename,'w');

					if($fp)
					{
						fputs($fp,$link_html);

						fclose($fp);
					}
				}

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
				else
				{
					$home_menu[$link_index]='<li><a href="'.$link_url.'" target="'.($target_blank?'_blank':'_self').'">'.$link_name.'</a></li>';
				}

				$home_menu=implode("\n",$home_menu);

				$fp=fopen('../../home/home_menu_'.$menu_language.'.html','w');

				fputs($fp,$home_menu);

				fclose($fp);
			}
		}

		if(empty($errorMsg))
		{
			header('Location: '.$_SERVER['PHP_SELF']);
			exit();
		}
	}
	elseif($action == 'open_link')
	{
		$link=$_GET['link'];

		if(strstr($link,'/') || !strstr($link,'.html'))
		{
			$link='';
			$action='';
		}
	}
	elseif($action == 'delete_link')
	{
		$link_index=intval($_GET['link_index']);

		$home_menu=file('../../home/home_menu_'.$menu_language.'.html');

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

		$fp=fopen('../../home/home_menu_'.$menu_language.'.html','w');

		fputs($fp,$home_menu);

		fclose($fp);

		header('Location: '.$_SERVER['PHP_SELF']);
		exit();
	}
	elseif($action == 'edit_top')
	{
		$home_top=file('../../home/home_top.html');

		$home_top=implode('',$home_top);
	}
	elseif($action == 'edit_notice')
	{
		$home_notice=file('../../home/home_notice.html');

		$notice_title=strip_tags($home_notice[0]);
		$notice_text=strip_tags(str_replace('<br />',"\n",$home_notice[1]),'<a>');
	}
	elseif($action == 'edit_news')
	{
		//$home_news=file('../../home/home_news.html');

		//$home_news=implode('',$home_news);

		if(file_exists("'../../home/home_news_".$menu_language.".html")){
			if(is_readable("../../home/home_news_".$menu_language.".html")){
				$home_news=file_get_contents("../../home/home_news_".$menu_language.".html","r");
				$home_news=implode('',$home_news);
			}
			else{
				$errorMsg=get_lang('HomePageFilesNotReadable');
			}
		}
		//File not exists
		else{
			$home_news=file_get_contents("../../home/home_news_".$menu_language.".html","r");
			$home_news=implode('',$home_news);
		}

	}
	elseif($action == 'insert_link')
	{
		$home_menu=file('../../home/home_menu_'.$menu_language.'.html');
	}
	elseif($action == 'edit_link')
	{
		$link_index=intval($_GET['link_index']);

		$home_menu=file('../../home/home_menu_'.$menu_language.'.html');

		$target_blank=false;
		$link_name='';
		$link_url='';

		foreach($home_menu as $key=>$enreg)
		{
			if($key == $link_index)
			{
				if(strstr($enreg,'target="_blank"'))
				{
					$target_blank=true;
				}

				$link_name=strip_tags($enreg);

				$enreg=explode('href="',$enreg);

				list($link_url)=explode('"',$enreg[sizeof($enreg)-1]);

				if(strstr($link_url,$_configuration['root_web']) && strstr($link_url,'?include='))
				{
					$link_url=explode('?include=',$link_url);

					$filename=$link_url[sizeof($link_url)-1];

					if(!strstr($filename,'/') && strstr($filename,'.html'))
					{
						$link_html=file($_configuration['root_web'].'home/'.$filename);

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
	}
}
else
{
	$result=api_sql_query("SELECT name FROM $tbl_category WHERE parent_id IS NULL ORDER BY tree_pos",__FILE__,__LINE__);

	$Categories=api_store_result($result);
}

Display::display_header($tool_name);

//api_display_tool_title($tool_name);

if($action == 'open_link')
{
?>

<div style="margin-bottom: 10px;">
<a href="<?php echo $_SERVER['PHP_SELF']; ?>">&lt;&lt; <?php echo get_lang('Back'); ?></a>
</div>

<?php
	include('../../home/'.$link);
}
elseif($action == 'edit_notice')
{
?>

<div style="margin-bottom: 10px;">
<a href="<?php echo $_SERVER['PHP_SELF']; ?>">&lt;&lt; <?php echo get_lang('Back'); ?></a>
</div>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>?action=<?php echo $action; ?>" method="post" style="margin:0px;">
<input type="hidden" name="formSent" value="1"/>

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
}
elseif($action == 'insert_link' || $action == 'edit_link')
{
?>

<div style="margin-bottom: 10px;">
<a href="<?php echo $_SERVER['PHP_SELF']; ?>">&lt;&lt; <?php echo get_lang('Back'); ?></a>
</div>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>?action=<?php echo $action; ?>" method="post" style="margin:0px;">
<input type="hidden" name="formSent" value="1"/>
<input type="hidden" name="link_index" value="<?php if($action == 'edit_link') echo $link_index; else echo '0'; ?>"/>
<input type="hidden" name="filename" value="<?php if($action == 'edit_link') echo $filename; else echo ''; ?>"/>

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
  <td nowrap="nowrap"><?php echo get_lang('LinkName'); ?> :</td>
  <td><input type="text" name="link_name" size="30" maxlength="50" value="<?php echo htmlentities($link_name); ?>" style="width: 350px;"/></td>
</tr>
<tr>
  <td nowrap="nowrap"><?php echo get_lang('LinkURL'); ?> (<?php echo get_lang('Optional'); ?>) :</td>
  <td><input type="text" name="link_url" size="30" maxlength="100" value="<?php if(empty($link_url)) echo 'http://'; else echo htmlentities($link_url); ?>" style="width: 350px;"/></td>
</tr>

<?php if($action == 'insert_link'): ?>
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
<?php endif; ?>

<tr>
  <td nowrap="nowrap"><?php echo get_lang('OpenInNewWindow'); ?> ?</td>
  <td><input class="checkbox" type="checkbox" name="target_blank" value="1" <?php if($target_blank) echo 'checked="checked"'; ?> /> <?php echo get_lang('Yes'); ?></td>
</tr>

<?php if($action == 'edit_link' && empty($link_url)): ?>
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
<?php else: ?>
<tr>
  <td>&nbsp;</td>
  <td><input type="submit" value="<?php echo get_lang('Ok'); ?>"/></td>
</tr>
<?php endif; ?>

</table>

</form>

<?php
}
elseif($action == 'edit_top' || $action == 'edit_news')
{
	if($action == 'edit_top')
	{
		$name="home_top";
		$open = $home_top;
	}
	else
	{
		$name="home_news";
		$user_selected_language = $_SESSION["_user"]["language"];
		if(!file_exists("../../home/home_news_".$user_selected_language.".html")){
			$platform_language=api_get_setting("platformLanguage");
			$open='../../home/home_news_'.$platform_language.'.html';
		}
		else{
			$open='../../home/home_news_'.$user_selected_language.'.html';
		}

		if(isset($_SESSION["user_language_choice"])){
			$language=$user_selected_language;
		}
		else{
			$language=api_get_setting("platformLanguage");
		}

		$open=file_get_contents($open);

	}
?>

<div style="margin-bottom: 10px;">
<a href="<?php echo $_SERVER['PHP_SELF']; ?>">&lt;&lt; <?php echo get_lang('Back'); ?></a>
</div>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>?action=<?php echo $action; ?>" method="post" style="margin:0px;">
<input type="hidden" name="formSent" value="1"/>

<?php
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
    //api_disp_html_area($open,isset($_POST[$open])?trim(stripslashes($_POST[$open])):${$open},'400px'); ?>
	
<?php
	if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
		echo (WCAG_Rendering::editor_header());
		WCAG_Rendering::prepare_admin_form($open)->display();
		echo (WCAG_Rendering::editor_footer());
	} else {
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
}
else
{
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%">
<tr>
  <td width="80%" colspan="2">
	<a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=edit_top"><img src="../img/edit.gif" border="0" title="<?php echo htmlentities(get_lang('Modify')); ?>"/></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=edit_top"><?php echo get_lang('EditHomePage'); ?></a>
  </td>
  <td width="20%">
	<a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=insert_link"><img src="../img/insert_row.png" border="0"/></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=insert_link"/><?php echo get_lang('InsertLink'); ?></a>
  </td>
</tr>
<tr>
  <td width="80%" colspan="2" valign="top">
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
	<tr>
	  <td colspan="2">

<?php
	include('../../home/home_top.html');
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
		<a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=edit_news"><img src="../img/edit.gif" border="0" title="<?php echo htmlentities(get_lang('Modify')); ?>"/></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=edit_news"><?php echo get_lang('EditNews'); ?></a>
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
	?>

		  <tr>
			<td><img src="../img/opendir.gif" border="0" alt=""/>&nbsp;<?php echo $enreg['name']; ?></td>
		  </tr>

	<?php
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
	  <td width="50%" valign="top">

	<?php

		$user_selected_language = $_SESSION["_user"]["language"];
		if(file_exists('../../home/home_news_'.$user_selected_language.'.html'))
		{
			include ('../../home/home_news_'.$user_selected_language.'.html');
		}
		else
		{
			$platform_language=api_get_setting("platformLanguage");
			if(file_exists('../../home/home_news_'.$platform_language.'.html')){
				include('../../home/home_news_'.$platform_language.'.html');
			}
			else{
				include ('../../home/home_news.html');
			}
		}
	?>

	  </td>
	</tr>
	</table>
  </td>
  <td width="20%" rowspan="3" valign="top">
	<div class="menu" style="width: 100%;">
	<?php
	api_display_language_form();
	?>
	<form id="loginform">
	<label><?php echo get_lang('LoginName'); ?></label>
	<input type="text" id="login" size="15" value="" disabled="disabled" />
	<label><?php echo get_lang('UserPassword'); ?></label>
	<input type="password" id="password" size="15" disabled="disabled" />
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
	$home_menu=file('../../home/home_menu_'.$menu_language.'.html');

	foreach($home_menu as $key=>$enreg)
	{
		$enreg=trim($enreg);

		if(!empty($enreg))
		{
			$edit_link='<a href="'.$_SERVER['PHP_SELF'].'?action=edit_link&amp;link_index='.$key.'"><img src="../img/edit.gif" border="0" style="margin-top: 2px;" title="'.htmlentities(get_lang('Modify')).'"/></a>';
			$delete_link='<a href="'.$_SERVER['PHP_SELF'].'?action=delete_link&amp;link_index='.$key.'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang('ConfirmYourChoice'))).'\')) return false;"><img src="../img/delete.gif" border="0" style="margin-top: 2px;" title="'.htmlentities(get_lang('Delete')).'"/></a>';

			echo str_replace(array('href="'.$_configuration['root_web'].'index.php?include=','</li>'),array('href="'.api_get_path(WEB_CODE_PATH).'admin/'.basename($_SERVER['PHP_SELF']).'?action=open_link&link=','<br />'.$edit_link.' '.$delete_link.'</li>'),$enreg);
		}
	}
?>

	</ul>
	</div>

	<br />
	&nbsp;&nbsp;<a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=edit_notice"><img src="../img/edit.gif" border="0" title="<?php echo htmlentities(get_lang('Modify')); ?>"/></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=edit_notice"><?php echo get_lang('EditNotice'); ?></a>

	<div class="note">

<?php
	include('../../home/home_notice.html');
?>

	</div>
	</div>
  </td>
</tr>
</table>

<?php
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
