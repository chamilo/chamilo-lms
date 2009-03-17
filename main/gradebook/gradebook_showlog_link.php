<?php // $Id: $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors
	Copyright (c) Isaac Flores Paz <florespaz@bidsoftperu.com>
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
$language_file = 'gradebook';
//$cidReset = true;
require_once ('../inc/global.inc.php');
require_once ('lib/be.inc.php');
require_once ('lib/gradebook_functions.inc.php');
require_once ('lib/fe/evalform.class.php');
api_block_anonymous_users();
block_students();

$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'].'?selectcat='.Security::remove_XSS($_GET['selectcat']),
	'name' => get_lang('Gradebook'
));
$interbreadcrumb[] = array (
	'url' => 'gradebook_showlog_link.php?visiblelink='.Security::remove_XSS($_GET['visiblelink']).'&amp;selectcat='.Security::remove_XSS($_GET['selectcat']),
	'name' => get_lang('GradebookQualifyLog')
);
Display :: display_header('');
echo '<div class="clear"></div>';
echo '<div class="actions">';
api_display_tool_title(get_lang('GradebookQualifyLog'));
echo '</div>';
/*
$t_user=	 Database :: get_main_table(TABLE_MAIN_USER);
$t_link_log = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINKEVAL_LOG);	
$evaledit = EvalLink :: load($_GET['visiblelink']);

$sql="SELECT lk.name,lk.description,lk.date_log,lk.weight,lk.visible,lk.type,us.username from ".$t_link_log." lk inner join ".$t_user." us on lk.user_id_log=us.user_id where lk.id_linkeval_log=".$evaledit[0]->get_id()." and lk.type='link';";
$result=api_sql_query($sql);
echo '<table width="100%" border="0" >';
	echo '<tr>';
		echo '<td align="center" class="gradebook-table-header"><strong>'.get_lang('GradebookNameLog').'</strong></td>';
		echo '<td align="center" class="gradebook-table-header"><strong>'.get_lang('GradebookDescriptionLog').'</strong></td>';
		echo '<td align="center" class="gradebook-table-header"><strong>'.get_lang('Date').'</strong></td>';
		echo '<td align="center" class="gradebook-table-header"><strong>'.get_lang('Weight').'</strong></td>';
		echo '<td align="center" class="gradebook-table-header"><strong>'.get_lang('GradebookVisibilityLog').'</strong></td>';
		echo '<td align="center" class="gradebook-table-header"><strong>'.get_lang('ResourceType').'</strong></td>';
		echo '<td align="center" class="gradebook-table-header"><strong>'.get_lang('GradebookWhoChangedItLog').'</strong></td>';
echo '</tr>';
while($row=Database::fetch_array($result)){

if ('0000-00-00 00:00:00'!=$row[2]) {
	$date_log=date('d-m-Y H:i:s',$row[2]);	
} else {
	$date_log='0000-00-00 00:00:00';
}
echo '<tr>';
		echo '<td align="center" Class="gradebook-table-body">'.$row[0].'</td>';
		echo '<td align="center" Class="gradebook-table-body">'.$row[1].'</td>';
		echo '<td align="center" Class="gradebook-table-body">'.$date_log.'</td>';
		echo '<td align="center" Class="gradebook-table-body">'.$row[3].'</td>';
		if (1 == $row[4]) {
			$visib=get_lang('GradebookVisible');
		} else {
			$visib=get_lang('GradebookInvisible');
		}
		echo '<td align="center" Class="gradebook-table-body">'.$visib.'</td>';
		echo '<td align="center" Class="gradebook-table-body">'.$row[5].'</td>';
		echo '<td align="center" Class="gradebook-table-body">'.$row[6].'</td>';
		echo '</tr>';
}
echo '</table>';
*/

$t_user     = Database :: get_main_table(TABLE_MAIN_USER);
$t_link_log = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINKEVAL_LOG);
$visible_link=Security::remove_XSS($_GET['visiblelink']);	
$evaledit   = EvalLink :: load($visible_link);
$sql="SELECT lk.name,lk.description,lk.date_log,lk.weight,lk.visible,lk.type,us.username from ".$t_link_log." lk inner join ".$t_user." us on lk.user_id_log=us.user_id where lk.id_linkeval_log=".$evaledit[0]->get_id()." and lk.type='link';";
$result=api_sql_query($sql);
$list_info=array();
while ($row=Database::fetch_row($result)) {
	$list_info[]=$row;
}

foreach($list_info as $key => $info_log) {
	$list_info[$key][2]=($info_log[2]) ? date('d-m-Y H:i:s',$info_log[2]) : '0000-00-00 00:00:00';
	$list_info[$key][4]=($info_log[4]==1) ? get_lang('GradebookVisible') : get_lang('GradebookInvisible');
}

$parameters=array('visiblelink'=>Security::remove_XSS($_GET['visiblelink']),'selectcat'=>Security::remove_XSS($_GET['selectcat']));


$table = new SortableTableFromArrayConfig($list_info, 1,20,'gradebooklink');
$table->set_additional_parameters($parameters);
$table->set_header(0, get_lang('GradebookNameLog'));
$table->set_header(1, get_lang('GradebookDescriptionLog'));
$table->set_header(2, get_lang('Date'));
$table->set_header(3, get_lang('Weight'));
$table->set_header(4, get_lang('GradebookVisibilityLog'));
$table->set_header(5, get_lang('ResourceType'));
$table->set_header(6, get_lang('GradebookWhoChangedItLog'));
$table->display();

Display :: display_footer();