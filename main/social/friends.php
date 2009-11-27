<?php
/* For licensing terms, see /dokeos_license.txt */

$language_file = array('admin');
require '../inc/global.inc.php';
require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'image.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';

$this_section = SECTION_SOCIAL;

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.1.3.1.pack.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.history_remote.pack.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.tabs.pack.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript">
	
	
function add_group (my_action,name_rs) {
	if(my_action=="show") {
		$("div#div_content_messages").html("");
		$("div#div_content_table_data").html("");
		$(".confirmation-message").remove();
		$(".normal-message").remove();
		$(".error-message").remove();
			$.ajax({
			contentType: "application/x-www-form-urlencoded",
			type: "GET",
			url: "group_add.php",
			data:"rs="+name_rs,
			success: function(datos) {
			 $("div#div_add_group").html(datos);
			}
		});
	}
}
		
		
			
function delete_friend (element_div) {
	id_image=$(element_div).attr("id");
	user_id=id_image.split("_");
	if (confirm("'.get_lang('Delete', '').'")) {
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			type: "POST",
			url: "../social/register_friend.php",
			data: "delete_friend_id="+user_id[1],
			success: function(datos) {
			//alert(datos);
			 $("div#"+"div_"+user_id[1]).hide("slow");
			 $("div#"+"div_"+user_id[1]).html("");
			 clear_form ();
			}
		});
	}
}
		
		
		
		
		
function search_image_social(element_html)  {
	name_search=$(element_html).attr("value");
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		type: "POST",
		url: "../social/show_search_image.inc.php",
		data: "search_name_q="+name_search,
		success: function(datos) {
			$("div#div_content_table").html(datos);
		}
	});
}
		
function show_icon_delete(element_html) {
	elem_id=$(element_html).attr("id");
	id_elem=elem_id.split("_");
	ident="#img_"+id_elem[1];
	$(ident).attr("src","../img/delete.gif");
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

$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));

Display :: display_header($tool_name, 'Groups');
SocialManager::show_social_menu();

echo '<div class="actions-title">';
echo get_lang('MyFriends');
echo '</div>';

	 
//$list_path_friends=array();
$request=api_is_xml_http_request();
$language_variable=api_xml_http_response_encode(get_lang('Contacts'));
//api_display_tool_title($language_variable);

$user_id=api_get_user_id();
$image_path = UserManager::get_user_picture_path_by_id ($user_id,'web',false,true);
?>
<div align="center" >
<table width="100%" border="0" cellpadding="0" cellspacing="0" >
  <tr>
    <td height="25" valign="top">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" >
      <tr>
        <td width="100%"  valign="top" class="social-align-box">&nbsp;&nbsp;<?php echo get_lang('Search') .'&nbsp;&nbsp; : &nbsp;&nbsp;'; ?>
        	<input class="social-search-image" type="text" class="search-image" id="id_search_image" name="id_search_image" value="" onkeyup="search_image_social(this)" />
        </td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="175" valign="top">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" >
      <tr>
		<td height="153" valign="top">
			<?php
			echo '<div id="div_content_table">';
			require_once 'show_search_image.inc.php';
			echo '</div>';
			?>
		</td>
        </tr>
    </table></td>
  </tr>
</table>
</div>
<?php
Display :: display_footer();
?>