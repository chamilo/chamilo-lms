<?php
$language_file = array('registration','messages','userInfo','admin');
$cidReset = true;
require '../inc/global.inc.php';
$track_online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
$tbl_my_user = Database :: get_main_table(TABLE_MAIN_USER);
$tbl_my_user_friend = Database :: get_main_table(TABLE_MAIN_USER_FRIEND);
$search=Security::remove_XSS($_POST['search']);
$current_date=date('Y-m-d H:i:s',time()); 
$html_form='<select id="id_search_name" name="id_search_name" size="8"" style="width:350px;">';
$user_id = api_get_user_id();
if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true') {
	$sql = 'SELECT  u.user_id as id,concat(u.firstname," ",u.lastname," ","( ",u.email," )") as name 
	FROM '.$tbl_my_user_friend.' uf ' .
 	'INNER JOIN '.$tbl_my_user.' AS u  ON uf.friend_user_id = u.user_id ' .
 	'WHERE relation_type<>6 AND friend_user_id<>'.(int)$user_id.' AND concat(u.firstName,u.lastName) like CONCAT("%","'.$search.'","%") ';
} elseif (api_get_setting('allow_social_tool')=='false' && api_get_setting('allow_message_tool')=='true') {
	$valid=api_get_setting('time_limit_whosonline');
	$sql='SELECT DISTINCT u.user_id as id,concat(u.firstname," ",u.lastname," ","( ",u.email," )") as name
	 FROM '.$tbl_my_user.' u INNER JOIN '.$track_online_table.' t ON u.user_id=t.login_user_id 
	 WHERE DATE_ADD(login_date,INTERVAL "'.$valid.'" MINUTE) >= "'.$current_date.'" AND concat(u.firstName,u.lastName) like CONCAT("%","'.$search.'","%") '; 	
}

$res=Database::query($sql,__FILE__,__LINE__);
while ($row=Database::fetch_array($res,'ASSOC')) {
	$html_form.='<option value="'.$row['id'].'">'.api_xml_http_response_encode($row['name']).'</option>';	
}
$html_form.='</select>';
echo $html_form;
?>