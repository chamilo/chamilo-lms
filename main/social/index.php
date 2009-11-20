<?php
/* For licensing terms, see /dokeos_license.txt */

$cidReset = true;
$language_file = array('registration','messages','userInfo','admin');
require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';

$this_section = SECTION_MYPROFILE;
$_SESSION['this_section']=$this_section;
api_block_anonymous_users();
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.1.3.1.pack.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.history_remote.pack.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.tabs.pack.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.js" type="text/javascript" language="javascript"></script>';
//$htmlHeadXtra[] = '<style rel="stylesheet" href="../inc/lib/javascript/thickbox.css" type="text/css" media="projection, screen">';
$htmlHeadXtra[]='<style type="text/css" media="all">@import "'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css";</style>';

if (api_get_setting('allow_message_tool')=='true') {
	$htmlHeadXtra[] ='<script type="text/javascript">
		function delete_message_js() {
			$(".message-content").animate({ opacity: "hide" }, "slow");
			$(".message-view").animate({ opacity: "show" }, "slow");
		}
	</script>';
}
$htmlHeadXtra[] = '<link rel="stylesheet" href="../inc/lib/javascript/jquery.tabs.css" type="text/css" media="print, projection, screen">';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/jquery.tabs.css" type="text/css" media="print, projection, screen">';
$htmlHeadXtra[] = '
        <!-- Additional IE/Win specific style sheet (Conditional Comments) -->
        <!--[if lte IE 7]>
        <link rel="stylesheet" href="../inc/lib/javascript/jquery.tabs-ie.css" type="text/css" media="projection, screen">
        <![endif]-->';
$_SESSION['social_exist']=true;
$_SESSION['social_dest'] = 'index.php';
$interbreadcrumb[]= array (
	'url' => '#',
	'name' => get_lang('ModifyProfile')
);
if ((api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true') ||(api_get_setting('allow_social_tool')=='true') && api_get_user_id()<>2 && api_get_user_id()<>0) {
	$interbreadcrumb[]= array (
	'url' => 'index.php?#remote-tab-1',
	'name' => get_lang('SocialNetwork')
	);
} elseif ((api_get_setting('allow_social_tool')=='false' && api_get_setting('allow_message_tool')=='true')) {
	$interbreadcrumb[]= array (
	'url' => 'index.php?#remote-tab-1',
	'name' => get_lang('MessageTool')
	);
}

Display :: display_header('');
if (isset($_GET['sendform'])) {
	$form_reply=array();
	$form_reply[]=urlencode($_POST['title']);
	$form_reply[]=urlencode(api_html_entity_decode($_POST['content']));
	$form_reply[]=$_POST['user_list'];
	$form_reply[]=$_POST['re_id'];
	$form_reply[]=urlencode($_POST['compose']);
	$form_reply[]=urlencode($_POST['id_text_name']);
	$form_reply[]=urlencode($_POST['save_form']);
	$form_info=implode(base64_encode('&%ff..x'),$form_reply);	
	$form_send_data_message='?form_reply='.$form_info;
} elseif (isset($_GET['inbox'])) {
	$form_delete=array();
	$form_delete[]=$_POST['action'];
	for ($i=0;$i<count($_POST['id']);$i++) {
		$form_delete[]=$_POST['id'][$i];
	}
	$form_info=implode(',',$form_delete);
	$form_send_data_message='?form_delete='.($form_info);
} elseif (isset($_GET['outbox'])) {
	$form_delete_outbox=array();
	$form_delete_outbox[]=$_POST['action'];
	for ($i=0;$i<count($_POST['out']);$i++) {
		$form_delete_outbox[]=$_POST['out'][$i];
	}
	$form_info_outbox=implode(',',$form_delete_outbox);
	$form_send_data_message='?form_delete_outbox='.($form_info_outbox);
} 
$form_url_send=isset($form_send_data_message) ? $form_send_data_message :'';

if(isset($_GET['add_group'])) {	
	$form_reply=array();
	$form_reply['name']			= urlencode($_POST['name']);
	$form_reply['description']	= urlencode(api_html_entity_decode($_POST['description']));
	$form_reply['url']			= $_POST['url'];
	$form_reply['picture']		= $_POST['picture'];
	$form_reply['add_group']	= $_POST['add_group'];	
	$form_info					= implode(base64_encode('&%ff..x'),$form_reply);	
	$form_send_data_message		= '?add_group='.$form_info;
}
$_GET['add_group'] = null;
$form_group_send=isset($form_send_data_message) ? $form_send_data_message :'';

//var_dump($form_group_send);

/* Social menu */

UserManager::show_menu();

?>
<div id="container-9">
    <ul>
        <li><a href="data_personal.inc.php"><span><?php Display :: display_icon('profile.png',get_lang('PersonalData')); echo '&nbsp;&nbsp;'.get_lang('PersonalData'); ?></span></a></li>
        <?php
       	if (api_get_setting('allow_message_tool')=='true') {
	       	?>
	        <li><a href="../messages/inbox.php<?php  echo $form_url_send; ?>"><span><?php Display :: display_icon('inbox.png',get_lang('Inbox')); echo '&nbsp;&nbsp;'.get_lang('Inbox');?></span></a></li>
	        <li><a href="../messages/outbox.php<?php echo $form_url_send; ?>"><span><?php Display :: display_icon('outbox.png',get_lang('Outbox') ); echo '&nbsp;&nbsp;'.get_lang('Outbox');?></span></a></li>
	        <?php 
	    }  	 	
        ?>
    </ul>    
</div>
<?php
Display :: display_footer();
?>