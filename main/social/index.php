<?php
$language_file = array('registration','messages');
$cidReset = true;

require '../inc/global.inc.php';
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
$this_section = SECTION_MYPROFILE;
$_SESSION['this_section']=$this_section;
api_block_anonymous_users();
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery-1.1.3.1.pack.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.history_remote.pack.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.tabs.pack.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript">
$(function() {
	$("#container-9").tabs({ remote: true});	     
});
function register_friend(element_input) {
	name_button=$(element_input).attr("id");
	name_div_id="id_"+name_button.substring(13);
	user_id=name_div_id.split("_");
	user_friend_id=user_id[1];
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#id_response").html("Cargando..."); },
		type: "POST",
		url: "../social/register_friend.php",
		data: "friend_id="+user_friend_id,
		success: function(datos) {
		 $("div#"+name_div_id).hide("slow");
		 $("#id_response").html(datos);
		}
	});		
}
function denied_friend (element_input) {
	name_button=$(element_input).attr("id");
	name_div_id="id_"+name_button.substring(13);
	user_id=name_div_id.split("_");
	friend_user_id=user_id[1];	
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#id_response").html("'.get_lang('Loading').'"); },
		type: "POST",
		url: "../social/register_friend.php",
		data: "denied_friend_id="+friend_user_id,
		success: function(datos) {
		 $("div#"+name_div_id).hide("slow");
		 $("#id_response").html(datos);
		}
	});
	
}
function delete_friend (element_div) {
	id_image=$(element_div).attr("id");
	user_id=id_image.split("_");
	if (confirm("'.get_lang('Delete').'")) {
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			/*$("#id_response").html("Cargando...");*/ },
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
function qualify_friend(element_div) {
	name_user=$(element_div).attr("title");
	id_full=$(element_div).attr("id");
	$("div#div_info_user").html(name_user);
	user_qualify=id_full.split("_");
	user_qualify_id=user_qualify[1];
	$("#user_cod_qualify").attr("value",user_qualify_id);
	get_image_url=$(element_div).attr("src");
	img_html="<img width=100 height=120 src="+get_image_url+" />";
	$("div#div_qualify_image").html(img_html);
}
function set_qualify_friend() {
	user_id_friend = $("#user_cod_qualify").attr("value");
	type_friend    = $("input[@name=list_type_friend]:checked").val();
	if (user_id_friend==0) {
		alert("'.get_lang('SelectedOneOption').'");
		return false;
	}
	if (type_friend==null) {
		alert("'.get_lang('SelectedOneOption').'");
		return false;		
	}	
	if (confirm("'.get_lang('QualifyContactsPersonal').'")) {
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			/*$("#id_response").html("Cargando...");*/ },
			type: "POST",
			url: "../social/register_friend.php",
			data: "user_id_friend_q="+user_id_friend+"&type_friend_q="+type_friend,
			success: function(datos) {
			alert(datos);
			clear_form();
			}
		});
	}
}
function show_icon_delete(element_html) {
	elem_id=$(element_html).attr("id");
	id_elem=elem_id.split("_");
	ident="#img_"+id_elem[1];
	$(ident).attr("src","../img/delete.gif");
	$(ident).attr("alt","'.get_lang('Delete').'");
	$(ident).attr("title","'.get_lang('Delete').'");
}
function hide_icon_delete(element_html)  {
	elem_id=$(element_html).attr("id");
	id_elem=elem_id.split("_");
	ident="#img_"+id_elem[1];
	$(ident).attr("src","../img/blank.gif");
	$(ident).attr("alt","");
	$(ident).attr("title","");
}
function search_image_social(element_html)  {
	name_search=$(element_html).attr("value");
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#div_content_table").html("'.get_lang('Searching').'"); },
		type: "POST",
		url: "../social/show_search_image.inc.php",
		data: "search_name_q="+name_search,
		success: function(datos) {
			$("div#div_content_table").html(datos);
		}
	});
}
function clear_form () {
$("input[@type=radio]").attr("checked", false);
$("div#div_qualify_image").html("");
$("div#div_info_user").html("");
}
</script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="../inc/lib/javascript/jquery.tabs.css" type="text/css" media="print, projection, screen">';
$htmlHeadXtra[] = '
        <!-- Additional IE/Win specific style sheet (Conditional Comments) -->
        <!--[if lte IE 7]>
        <link rel="stylesheet" href="../inc/lib/javascript/jquery.tabs-ie.css" type="text/css" media="projection, screen">
        <![endif]-->
        ';
$htmlHeadXtra[] = '<style type="text/css" media="screen, projection">
/* Not required for Tabs, just to make this demo look better... */

* html body {
    font-size: 100%; /* @ IE */
}
h1 {
    margin: 1em 0 1.5em;
    font-size: 18px;
}
h2 {
    margin: 2em 0 1.5em;
    font-size: 16px;
}
p {
    margin: 0;
}
pre, pre+p, p+p {
    margin: 1em 0 0;
}
code {
    font-family: "Courier New", Courier, monospace;
}
</style>';
$_SESSION['social_exist']=true;
$_SESSION['social_dest'] = 'index.php';
$interbreadcrumb[]= array (
	'url' => '#',
	'name' => get_lang('ModifyProfile')
);
$interbreadcrumb[]= array (
	'url' => $_SESSION['social_dest'],
	'name' => get_lang('Social')
);
Display :: display_header('');
?>
<div id="container-9">
    <ul>
        <li><a href="data_personal.inc.php"><span><?php echo get_lang('DataPersonals') ?></span></a></li>
        <?php 
       	if (api_get_setting('allow_message_tool')=='true') { 
       	?>        
        <li><a href="../messages/inbox.php"><span><?php echo get_lang('Inbox') ?></span></a></li>
        <li><a href="../messages/outbox.php"><span><?php echo get_lang('Outbox') ?></span></a></li>
        <?php }
  	 	if (api_get_setting('allow_social_tool')=='true') {      
        ?>
        <li><a href="select_friend_response.php"><span><?php echo get_lang('Invitation') ?></span></a></li>
        <li><a href="contacts.inc.php"><span><?php echo get_lang('Contacts') ?></span></a></li>
        <li><a href="group_contact.inc.php"><span><?php echo get_lang('ContactsGroups') ?></span></a></li>
        <?php
  	 	}
        ?>    
    </ul>
    <?php echo '<div id="show"></div>';?>
</div>
<?php
Display :: display_footer();
?>