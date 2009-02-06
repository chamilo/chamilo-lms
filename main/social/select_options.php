<?php
$language_file = 'registration';
$cidReset = true;

require '../inc/global.inc.php';
$track_online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
$tbl_my_user = Database :: get_main_table(TABLE_MAIN_USER);
$tbl_my_user_friend = Database :: get_main_table(TABLE_MAIN_USER_FRIEND);
$search=$_POST['search'];
$html_form='<select id="id_search_name" name="id_search_name" size="8"" class="message-select-box">';
$sql='SELECT DISTINCT u.user_id as id,concat(u.firstname," ",u.lastname," ","( ",u.email," )") as name
FROM '.$tbl_my_user.' u INNER JOIN '.$track_online_table.' t ON u.user_id=t.login_user_id';
if (api_get_setting('allow_social_tool')=='true') {
	$sql.='INNER JOIN '.$tbl_my_user_friend.' uf ON uf.friend_user_id=u.user_id ';
}  
$res=api_sql_query($sql,__FILE__,__LINE__);
while ($row=Database::fetch_array($res,'ASSOC')) {
	$html_form.='<option value="'.$row['id'].'">'.$row['name'].'</option>';	
}
$html_form.='</select>';
echo $html_form;
?>