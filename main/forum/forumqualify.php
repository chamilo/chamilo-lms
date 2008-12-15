<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 108, ru du Corbeau, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
* 	@package dokeos.forum
*/
$language_file=array('admin','forum');
require_once '../inc/global.inc.php';
require 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';
$nameTools = get_lang('Forum');
$this_section = SECTION_COURSES;

$current_thread=get_thread_information($_GET['thread']); // note: this has to be validated that it is an existing thread
$current_forum=get_forum_information($current_thread['forum_id']); // note: this has to be validated that it is an existing forum.
$current_forum_category=get_forumcategory_information($current_forum['forum_category']);
$whatsnew_post_info=$_SESSION['whatsnew_post_info'];
$interbreadcrumb[]=array("url" => "index.php?search=".Security::remove_XSS(urlencode($_GET['search'])),"name" => $nameTools);
$interbreadcrumb[]=array("url" => "viewforumcategory.php?forumcategory=".$current_forum_category['cat_id']."&amp;search=".Security::remove_XSS(urlencode($_GET['search'])),"name" => prepare4display($current_forum_category['cat_title']));

if (isset($_GET['gradebook']) && $_GET['gradebook']=='view') {
	$info_thread=get_thread_information(Security::remove_XSS($_GET['thread']));
	$interbreadcrumb[]=array("url" => "viewforum.php?forum=".$info_thread['forum_id']."&amp;search=".Security::remove_XSS(urlencode($_GET['search'])),"name" => prepare4display($current_forum['forum_title']));
} else {
	$interbreadcrumb[]=array("url" => "viewforum.php?forum=".Security::remove_XSS($_GET['forum'])."&amp;search=".Security::remove_XSS(urlencode($_GET['search'])),"name" => prepare4display($current_forum['forum_title']));
}
if ($message<>'PostDeletedSpecial') {
	
	if (isset($_GET['gradebook']) && $_GET['gradebook']=='view') {
		$info_thread=get_thread_information(Security::remove_XSS($_GET['thread']));
		$interbreadcrumb[]=array("url" => "viewthread.php?forum=".$info_thread['forum_id']."&amp;thread=".Security::remove_XSS($_GET['thread']),"name" => prepare4display($current_thread['thread_title']));
	} else {
		$interbreadcrumb[]=array("url" => "viewthread.php?forum=".Security::remove_XSS($_GET['forum'])."&amp;thread=".Security::remove_XSS($_GET['thread']),"name" => prepare4display($current_thread['thread_title']));
	}
}

Display::display_header('');
$userinf=api_get_user_info(api_get_user_id());
if ($userinf['status']=='1') {
	echo "<strong>".get_lang('ThreadQualification')."</strong>";
	echo "<br />";
	$current_thread=get_thread_information($_GET['thread']);
	$userid=(int)$_GET['user_id'];
	$threadid=$current_thread['thread_id'];
	//show current qualify in my form
	$qualify=current_qualify_of_thread($threadid,api_get_session_id());
	//show max qualify in my form
	$max_qualify=show_qualify('2',$_GET['cidReq'],$_GET['forum'],$userid,$threadid);
	require_once 'forumbody.inc.php';
	$value_return = store_theme_qualify($userid,$threadid,$_REQUEST['idtextqualify'],api_get_user_id(),date("Y-m-d H:i:s"),api_get_session_id());
	$url='cidReq='.Security::remove_XSS($_GET['cidReq']).'&forum='.Security::remove_XSS($_GET['forum']).'&thread='.Security::remove_XSS($_GET['thread']).'&post='.Security::remove_XSS($_GET['post']).'&user_id='.Security::remove_XSS($_GET['user_id']);
	$current_qualify_thread=show_qualify('1',$_GET['cidReq'],$_GET['forum'],$userid,$threadid);
		
	if ($value_return[0]!=$_REQUEST['idtextqualify'] && $value_return[1]=='update') {		
		store_qualify_historical('1','',$_GET['forum'],$userid,$threadid,$_REQUEST['idtextqualify'],api_get_user_id());
	}
	
	if (!empty($_REQUEST['idtextqualify']) && $_REQUEST['idtextqualify'] > $max_qualify) { 
		$return_message = get_lang('QualificationNotBeGreaterThanMaxScore');	
		Display :: display_error_message($return_message,false);
	}
		
	// show qualifications history
	$user_id_thread = (int)$_GET['user_id'];
	$opt=Database::escape_string($_GET['type']);
	$qualify_historic = get_historical_qualify($user_id_thread, $threadid, $opt);
	$counter= count($qualify_historic);	
	if ($counter>0) {
		echo '<h4>'.get_lang('QualificationChangesHistory').'</h4>';	
		if ($_GET['type'] == 'false') {
			echo '<div style="float:left; clear:left">'.get_lang('OrderBy').'&nbsp;:<a href="forumqualify.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&thread='.$threadid.'&user_id='.Security::remove_XSS($_GET['user_id']).'&type=true">'.get_lang('MoreRecent').'</a>&nbsp;|
					'.get_lang('Older').'
				  </div>';
		} else {
			echo '<div style="float:left; clear:left">'.get_lang('OrderBy').'&nbsp;:'.get_lang('MoreRecent').' |
					<a href="forumqualify.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&thread='.$threadid.'&user_id='.Security::remove_XSS($_GET['user_id']).'&type=false">'.get_lang('Older').'</a>&nbsp;
				  </div>';
		}				
		$table_list.= '<br /><br /><table class="data_table" style="width:100%">';	
		$table_list.= '<tr>';
		$table_list.= '<th width="50%">'.get_lang('WhoChanged').'</th>';
		$table_list.= '<th width="10%">'.get_lang('NoteChanged').'</th>';				
		$table_list.= '<th width="40%">'.get_lang('DateChanged').'</th>';	
		$table_list.= '</tr>';
		for($i=0;$i<count($qualify_historic);$i++) {
			    $my_user_info=api_get_user_info($qualify_historic['user_id']);
				$name = $my_user_info['firstName']." ".$my_user_info['lastName'];					
				$table_list.= '<tr class="$class"><td>'.$name.'</td>';			
				$table_list.= '<td>'.$qualify_historic[$i]['qualify'].'</td>';
				$table_list.= '<td>'.$qualify_historic[$i]['qualify_time'].'</td></tr>';			
		}						
		$table_list.= '</table>';
		echo $table_list;
		
	} else {
		echo get_lang('NotChanged');
	}			
} else {
	api_not_allowed();	
}
//footer
Display::display_footer();