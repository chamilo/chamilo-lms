<?php
$language_file = array('registration','messages','userInfo','admin');
$cidReset = true;
require '../inc/global.inc.php';
$track_online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
$tbl_my_user = Database :: get_main_table(TABLE_MAIN_USER);
$tbl_my_user_friend = Database :: get_main_table(TABLE_MAIN_USER_FRIEND);
$search=$_POST['search'];
$date_inter=date('Y-m-d H:i:s',time()-120); 
$html_form='<select id="id_search_name" name="id_search_name" size="8"" class="message-select-box">';
$user_id = api_get_user_id();
$sql = 'SELECT  u.user_id as id,concat(u.firstname," ",u.lastname," ","( ",u.email," )") as name ' .
		'FROM '.$tbl_my_user_friend.' uf ' .
 		'INNER JOIN '.$tbl_my_user.' AS u  ON uf.friend_user_id = u.user_id ' .
 		'WHERE relation_type<>6 AND friend_user_id<>'.(int)$user_id.' AND uf.user_id='.(int)$user_id.
 		' AND concat(u.firstName,u.lastName) like CONCAT("%","'.$search.'","%") ';
 		
if (api_get_setting('allow_social_tool')=='true') {
	//$sql.=' INNER JOIN '.$tbl_my_user_friend.' uf ON uf.friend_user_id=u.user_id ';
}  
$res=api_sql_query($sql,__FILE__,__LINE__);
while ($row=Database::fetch_array($res,'ASSOC')) {
	$html_form.='<option value="'.$row['id'].'">'.api_xml_http_response_encode($row['name']).'</option>';	
}
$html_form.='</select>';
echo $html_form;
?>
