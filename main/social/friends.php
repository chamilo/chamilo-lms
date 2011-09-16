<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Initialization
 */
$language_file = array('userInfo');
$cidReset=true;
require_once '../inc/global.inc.php';
require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

$this_section = SECTION_SOCIAL;

$htmlHeadXtra[] = '<script type="text/javascript">

function delete_friend (element_div) {
	id_image=$(element_div).attr("id");
	user_id=id_image.split("_");
	if (confirm("'.get_lang('Delete', '').'")) {
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			type: "POST",
			url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=delete_friend",
			data: "delete_friend_id="+user_id[1],
			success: function(datos) {
			 $("div#"+"div_"+user_id[1]).hide("slow");
			 $("div#"+"div_"+user_id[1]).html("");
			 clear_form ();
			}
		});
	}
}
			
		
function search_image_social()  {
	var name_search = $("#id_search_image").attr("value");	
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		type: "POST",
		url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=show_my_friends",
		data: "search_name_q="+name_search,
		success: function(datos) {
			$("#friend_table").html(datos);
		}
	});
}
		
function show_icon_delete(element_html) {
	elem_id=$(element_html).attr("id");
	id_elem=elem_id.split("_");
	ident="#img_"+id_elem[1];
	$(ident).attr("src","../img/delete.png");
	$(ident).attr("alt","'.get_lang('Delete', '').'");
	$(ident).attr("title","'.get_lang('Delete', '').'");
}
		

function hide_icon_delete(element_html)  {
	elem_id=$(element_html).attr("id");
	id_elem=elem_id.split("_");
	ident="#img_"+id_elem[1];
	$(ident).attr("src","../img/blank.gif");
	$(ident).attr("alt","");
	$(ident).attr("title","");
}
		
function clear_form () {
	$("input[@type=radio]").attr("checked", false);
	$("div#div_qualify_image").html("");
	$("div#div_info_user").html("");
}
	
</script>';

$interbreadcrumb[]= array ('url' =>'profile.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Friends'));

Display :: display_header($tool_name, 'Groups');

echo '<div id="social-content">';

	echo '<div id="social-content-left">';	
		//this include the social menu div
		SocialManager::show_social_menu('friends');	
	echo '</div>';
	echo '<div id="social-content-right">';
	
$language_variable	= api_xml_http_response_encode(get_lang('Contacts'));
$user_id	= api_get_user_id();

$list_path_friends	= array();
$user_id	    = api_get_user_id();
$name_search = isset($_POST['search_name_q']) ? $_POST['search_name_q']: null;
$number_friends = 0;

if (isset($name_search) && $name_search!='undefined') {
	$friends = SocialManager::get_friends($user_id,null,$name_search);
} else {
	$friends = SocialManager::get_friends($user_id);
}


if (count($friends) == 0 ) {
	echo get_lang('NoFriendsInYourContactList').'<br /><br />';
	echo '<a href="search.php">'.get_lang('TryAndFindSomeFriends').'</a>';	
} else {
	echo get_lang('Search') .'&nbsp;&nbsp; : &nbsp;&nbsp;'; ?>
	<input class="social-search-image" type="text" id="id_search_image" name="id_search_image" onkeyup="search_image_social()" />
	<?php				
		$friend_html = '';
		$number_of_images = 8;
		
		$number_friends = count($friends);
		$j=0;		
							
		$friend_html.= '<table  id="friend_table" width="95%" border="0" cellpadding="0" cellspacing="0" bgcolor="" >';
		for ($k=0;$k<$number_friends;$k++) {
			$friend_html.='<tr><td valign="top">';
		
			while ($j<$number_friends) {
				if (isset($friends[$j])) {
					$friend = $friends[$j];
					$user_name = api_xml_http_response_encode($friend['firstName'].' '.$friend['lastName']);
					$friends_profile = SocialManager::get_picture_user($friend['friend_user_id'], $friend['image'], 92);
					$friend_html.='<div onMouseover="show_icon_delete(this)" onMouseout="hide_icon_delete(this)" class="image-social-content" id=div_'.$friends[$j]['friend_user_id'].'>';
					$friend_html.='<span><a href="profile.php?u='.$friend['friend_user_id'].'"><center><img src="'.$friends_profile['file'].'" style="height:60px;" id="imgfriend_'.$friend['friend_user_id'].'" title="'.$user_name.'" /></center></a></span>';
					$friend_html.='<img onclick="delete_friend(this)" id=img_'.$friend['friend_user_id'].' src="../img/blank.gif" alt="" title=""  class="image-delete" /> <center class="friend">'.$user_name.'</center></div>';				
				}
				$j++;
			}
			$friend_html.='</td></tr>';
		}
		$friend_html.='<br/></table>';
		echo $friend_html;
	}
	echo '</div>';
	echo '</div>';	
Display :: display_footer();