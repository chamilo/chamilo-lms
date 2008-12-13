<?php // $Id: $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

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

Display :: display_header('');
$t_user=	 Database :: get_main_table(TABLE_MAIN_USER);
$t_link_log = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINKEVAL_LOG);	
$evaledit = EvalLink :: load($_GET['visiblelink']);

$sql="SELECT lk.name,lk.description,lk.date_log,lk.weight,lk.visible,lk.type,us.username from ".$t_link_log." lk inner join ".$t_user." us on lk.user_id_log=us.user_id where lk.id_linkeval_log=".$evaledit[0]->get_id()." and lk.type='link';";
$result=api_sql_query($sql);
echo '<table width="100%" border="0" >';
	echo '<tr>';
		echo '<td align="center" class="Gradebook-table-header"><strong>'.get_lang('GradebookNameLog').'</strong></td>';
		echo '<td align="center" class="Gradebook-table-header"><strong>'.get_lang('GradebookDescriptionLog').'</strong></td>';
		echo '<td align="center" class="Gradebook-table-header"><strong>'.get_lang('Date').'</strong></td>';
		echo '<td align="center" class="Gradebook-table-header"><strong>'.get_lang('Weight').'</strong></td>';
		echo '<td align="center" class="Gradebook-table-header"><strong>'.get_lang('GradebookVisibilityLog').'</strong></td>';
		echo '<td align="center" class="Gradebook-table-header"><strong>'.get_lang('ResourceType').'</strong></td>';
		echo '<td align="center" class="Gradebook-table-header"><strong>'.get_lang('GradebookWhoChangedItLog').'</strong></td>';
echo '</tr>';
while($row=Database::fetch_array($result)){

if ('0000-00-00 00:00:00'!=$row[2]) {
	$date_log=date('d-m-Y H:i:s',$row[2]);	
} else {
	$date_log='0000-00-00 00:00:00';
}
echo '<tr>';
		echo '<td align="center" Class="Gradebook-table-body">'.$row[0].'</td>';
		echo '<td align="center" Class="Gradebook-table-body">'.$row[1].'</td>';
		echo '<td align="center" Class="Gradebook-table-body">'.$date_log.'</td>';
		echo '<td align="center" Class="Gradebook-table-body">'.$row[3].'</td>';
		if (1 == $row[4]) {
			$visib=get_lang('GradebookVisible');
		} else {
			$visib=get_lang('GradebookInvisible');
		}
		echo '<td align="center" Class="Gradebook-table-body">'.$visib.'</td>';
		echo '<td align="center" Class="Gradebook-table-body">'.$row[5].'</td>';
		echo '<td align="center" Class="Gradebook-table-body">'.$row[6].'</td>';
		echo '</tr>';
}
echo '</table>';
Display :: display_footer();