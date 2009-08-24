<?php
// $Id: btf_functions.php 20027 2009-04-23 19:18:54Z cfasanando $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003 Ghent University
	Copyright (c) 2001 Universite Catholique de Louvain
	Copyright (c) various contributors
	Copyright (c) Bart Mollet, Hogeschool Gent

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
*                  HOME PAGE FUNCTIONS (BASIC TOOLS FIXED)
*
*	This page, included in every course's index.php is the home
*	page.To make administration simple, the professor edits his
*	course from it's home page. Only the login detects that the
*	visitor is allowed to activate, deactivate home page links,
*	access to Professor's tools (statistics, edit forums...).
*
*	@package dokeos.course_home
==============================================================================
*/

function showtools2($cat)
{
	global $_user, $charset;

	$TBL_ACCUEIL = Database :: get_course_table(TABLE_TOOL_LIST);
	$TABLE_TOOLS = Database :: get_main_table(TABLE_MAIN_COURSE_MODULE);

	$numcols = 3;
	$table = new HTML_Table('width="100%"');
	$toolsRow_all = array ();
	switch ($cat)
	{
		case 'Basic' :
			$sql = "SELECT a.*, t.image img, t.row, t.column  FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
									WHERE a.link=t.link AND t.position='basic' ORDER BY t.row, t.column";
			break;

		case 'External' :
			if (api_is_allowed_to_edit())
			{
				$sql = "SELECT a.*, t.image img FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
										WHERE (a.link=t.link AND t.position='external')
										OR (a.visibility <= 1 AND (a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND a.image=t.image)
										ORDER BY a.id";
			}
			else
			{
				$sql = "SELECT a.*, t.image img FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
										WHERE a.visibility = 1 AND ((a.link=t.link AND t.position='external')
										OR ((a.image = 'external.gif' OR a.image = 'scormbuilder.gif' OR t.image = 'blog.gif') AND a.image=t.image))
										 ORDER BY a.id";
			}
			break;

		case 'courseAdmin' :
			$sql = "SELECT a.*, t.image img, t.row, t.column  FROM $TBL_ACCUEIL a, $TABLE_TOOLS t
									WHERE admin=1 AND a.link=t.link ORDER BY t.row, t.column";
			break;

		case 'platformAdmin' :
			$sql = "SELECT *, image img FROM $TBL_ACCUEIL WHERE visibility = 2 ORDER BY id";
	}
	$result = api_sql_query($sql, __FILE__, __LINE__);

	// grabbing all the tools from $course_tool_table
	while ($tempRow = mysql_fetch_array($result))
	{
		/*
		if ($tempRow['img'] !== "scormbuilder.gif" AND $tempRow['img'] !== "blog.gif")
		*/
		if ($tempRow['img'] != 'file_html.gif' && $tempRow['img'] != 'file_html_na.gif'
			&& $tempRow['img'] != 'scormbuilder.gif' && $tempRow['img'] != 'scormbuilder_na.gif'
			&& $tempRow['img'] != 'blog.gif' && $tempRow['img'] != 'blog_na.gif'
			&& $tempRow['img'] != 'external.gif' && $tempRow['img'] != 'external_na.gif')
		{
			$tempRow['name_translated'] = get_lang(ucfirst($tempRow['name']));
		}
		$toolsRow_all[] = $tempRow;
	}
	// grabbing all the links that have the property on_homepage set to 1
	if ($cat == "External")
	{
		$tbl_link = Database :: get_course_table(TABLE_LINK);
		$tbl_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
		if (api_is_allowed_to_edit())
		{
			$sql_links = "SELECT tl.*, tip.visibility
								FROM $tbl_link tl
								LEFT JOIN $tbl_item_property tip ON tip.tool='link' AND tip.ref=tl.id
								WHERE tl.on_homepage='1' AND tip.visibility != 2";
		}
		else
		{
			$sql_links = "SELECT tl.*, tip.visibility
								FROM $tbl_link tl
								LEFT JOIN $tbl_item_property tip ON tip.tool='link' AND tip.ref=tl.id
								WHERE tl.on_homepage='1' AND tip.visibility = 1";
		}
		$result_links = api_sql_query($sql_links);
		while ($links_row = mysql_fetch_array($result_links))
		{
			$properties = array ();
			$properties['name'] = $links_row['title'];
			$properties['link'] = $links_row['url'];
			$properties['visibility'] = $links_row['visibility'];
			$properties['img'] = 'external.gif';
			$properties['adminlink'] = api_get_path(WEB_CODE_PATH).'link/link.php?action=editlink&amp;id='.$links_row['id'];
			$toolsRow_all[] = $properties;
		}
	}

	$cell_number = 0;
	// draw line between basic and external, only if there are entries in External
	if ($cat == "External" && count($toolsRow_all))
	{
		$table->setCellContents(0, 0, '<hr noshade="noshade" size="1"/>');
		$table->updateCellAttributes(0, 0, 'colspan="3"');
		$cell_number += $numcols;
	}

	foreach ($toolsRow_all as $toolsRow)
	{
		if (api_get_session_id()!=0 && in_array($toolsRow['name'],array('course_maintenance','course_setting'))) {
				continue;
		}
		
		$cell_content = '';
		// the name of the tool
		$tool_name = ($toolsRow['name_translated'] != "" ? $toolsRow['name_translated'] : htmlspecialchars($toolsRow['name'],ENT_QUOTES,$charset)); // RH: added htmlspecialchars

		$link_annex = '';
		// the url of the tool
		if ($toolsRow['img'] != "external.gif")
		{
			$toolsRow['link'] = api_get_path(WEB_CODE_PATH).$toolsRow['link'];
			$qm_or_amp = ((strpos($toolsRow['link'], '?') === FALSE) ? '?' : '&amp;');
			$link_annex = $qm_or_amp.api_get_cidreq();
		}
		else // if an external link ends with 'login=', add the actual login...
			{
			$pos = strpos($toolsRow['link'], "?login=");
			$pos2 = strpos($toolsRow['link'], "&amp;login=");
			if ($pos !== false or $pos2 !== false)
			{
				$link_annex = $_user['username'];
			}
		}

		// setting the actual image url
		$toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img'];

		// VISIBLE
		if ($toolsRow['visibility'] or $cat == 'courseAdmin' or $cat == 'platformAdmin')
		{	
			if(strpos($toolsRow['name'],'visio_')!==false)
			{	
				$cell_content .= '<a  href="javascript: void(0);" onclick="window.open(\'' . $toolsRow['link'].$link_annex . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '"><img src="'.$toolsRow['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>';
			}
			else if(strpos($toolsRow['name'],'chat')!==false && api_get_course_setting('allow_open_chat_window')==true)
			{					
				/*
				$cell_content .= '<a href="#" onclick="window.open(\'' .$toolsRow['link'].$link_annex. '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '"><img src="'.$toolsRow['img'].'" alt="'.get_lang(ucfirst($toolsRow['name'])).' " align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']				
				*/				
				$cell_content .= '<a href="javascript: void(0);" onclick="window.open(\'' .$toolsRow['link'].$link_annex. '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '"><img src="'.$toolsRow['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']				
			}			
			else 
			{
				/*
				$cell_content .= '<a href="'.$toolsRow['link'].$link_annex.'" target="'.$toolsRow['target'].'"><img src="'.$toolsRow['img'].'" alt="'.get_lang(ucfirst($toolsRow['name'])).' " align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
				*/
				$cell_content .= '<a href="'.$toolsRow['link'].$link_annex.'" target="'.$toolsRow['target'].'"><img src="'.$toolsRow['img'].'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
			}			
		}
		// INVISIBLE
		else
		{
			if (api_is_allowed_to_edit())
			{
				if(strpos($toolsRow['name'],'visio_')!==false)
				{	
					$cell_content .= '<a  href="javascript: void(0);" onclick="window.open(\'' . $toolsRow['link'].$link_annex . '\',\'window_visio'.$_SESSION['_cid'].'\',config=\'height=\'+730+\', width=\'+1020+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '"><img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; 
				}
				else if(strpos($toolsRow['name'],'chat')!==false && api_get_course_setting('allow_open_chat_window')==true)
				{					
					/*
					$cell_content .= '<a href="#" onclick="window.open(\'' .$toolsRow['link'].$link_annex. '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '" class="invisible"><img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" alt="'.get_lang(ucfirst($toolsRow['name'])).' " align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
					*/
					$cell_content .= '<a href="javascript: void(0);" onclick="window.open(\'' .$toolsRow['link'].$link_annex. '\',\'window_chat'.$_SESSION['_cid'].'\',config=\'height=\'+380+\', width=\'+625+\', left=2, top=2, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no\')" target="' . $toolsRow['target'] . '" class="invisible"><img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n"; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']
				}			
				else 
				{
					/*
					$cell_content .= '<a href="'.$toolsRow['link'].$link_annex.'" target="'.$toolsRow['target'].'" class="invisible"><img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" alt="'.get_lang(ucfirst($toolsRow['name'])).' " align="absmiddle" border="0">'.$tool_name.'</a>'."\n";// don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']				
					*/
					$cell_content .= '<a href="'.$toolsRow['link'].$link_annex.'" target="'.$toolsRow['target'].'" class="invisible"><img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'.$tool_name.'</a>'."\n";// don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']				
				}		
			}
			else
			{
				/*
				$cell_content .= '<img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" alt="'.get_lang(ucfirst($toolsRow['name'])).' " align="absmiddle" border="0">'; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']	
				*/	
				$cell_content .= '<img src="'.str_replace(".gif", "_na.gif", $toolsRow['img']).'" title="'.$tool_name.'" alt="'.$tool_name.'" align="absmiddle" border="0">'; // don't replace img with display::return_icon because $toolsRow['img'] = api_get_path(WEB_IMG_PATH).$toolsRow['img']	
				$cell_content .= '<span class="invisible">'.$tool_name.'</span>';
			}
		}

		$lnk = array ();
		if (api_is_allowed_to_edit() and $cat != "courseAdmin" && !strpos($toolsRow['link'], 'learnpath_handler.php?learnpath_id'))
		{			
			if ($toolsRow["visibility"])
			{
				$link['name'] = Display::return_icon('remove.gif', get_lang('Deactivate'), array('style' => 'vertical-align:middle;'));
				$link['cmd'] = "hide=yes";
				$lnk[] = $link;
			}
			else
			{
				$link['name'] = Display::return_icon('add.gif', get_lang('Activate'), array('style' => 'vertical-align:middle;'));
				$link['cmd'] = "restore=yes";
				$lnk[] = $link;

				/*if($toolsRow["img"] == $dokeosRepositoryWeb."img/external.gif")
				{
					$link['name'] = get_lang('Remove'); $link['cmd']  = "remove=yes";
					if ($toolsRow["visibility"]==2 and $cat=="platformAdmin")
					{
						$link['name'] = get_lang('Delete'); $link['cmd'] = "askDelete=yes";
						$lnk[] = $link;
					}
				}*/
			}
			//echo "<div class=courseadmin>";
			if (is_array($lnk))
			{
				foreach ($lnk as $thisLnk)
				{
					if ($toolsRow['adminlink'])
					{
						$cell_content .= '<a href="'.$properties['adminlink'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
						//echo "edit link:".$properties['adminlink'];
					}
					else
					{
						$cell_content .= "<a href=\"".api_get_self()."?id=".$toolsRow["id"]."&amp;".$thisLnk['cmd']."\">".$thisLnk['name']."</a>";
					}
				}
			}

			// RH: Allow editing of invisible homepage links (modified external_module)
			/*
			if ($toolsRow["added_tool"] == 1 && api_is_allowed_to_edit() && !$toolsRow["visibility"])
			*/
			if ($toolsRow["added_tool"] == 1 && api_is_allowed_to_edit() && !$toolsRow["visibility"]
				&& $toolsRow['image'] != 'scormbuilder.gif' && $toolsRow['image'] != 'scormbuilder_na.gif')
			{
				$cell_content .= "<a class=\"nobold\" href=\"".api_get_path(WEB_CODE_PATH).'external_module/external_module.php'."?id=".$toolsRow["id"]."\">".get_lang("Edit")."</a>";
			}
		}
		$table->setCellContents($cell_number / $numcols, ($cell_number) % $numcols, $cell_content);
		$table->updateCellAttributes($cell_number / $numcols, ($cell_number) % $numcols, 'width="32%" height="42"');
		$cell_number ++;
	}
	$table->display();
} // end function showtools2($cat)
?>
