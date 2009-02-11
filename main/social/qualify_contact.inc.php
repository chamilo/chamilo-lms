<?php 
$language_file=array('registration','messages','userInfo','admin');
require_once '../inc/global.inc.php';
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once '../inc/lib/social.lib.php';
$user_friend=$_POST['user_friend'];
$user_info=api_get_user_info($user_friend);
$list_of_options=array();
$list_of_options=UserFriend::show_list_type_friends();
$number_list=count($list_of_options);
?>
<table width="280" border="0">
    <tr>
        <td>
        	<table width="280" border="0" cellspacing="0" cellpadding="0">
<?php
for ($k=0;$k<$number_list;$k++) {
?>
		    <tr height="20">
		        <td width="20"><input type="radio" class="radio" name="list_type_friend"  value="<?php echo $list_of_options[$k]['id']; ?>" /></td>
		        <td width="260"><?php echo  utf8_encode(get_lang($list_of_options[$k]['title'])); ?></td>
		    </tr>
<?php
}
?>
        </table>
        </td>
    </tr>
	    <tr>
        <td><input type="button" value="<?php echo utf8_encode(get_lang('AttachToGroup')); ?>" onclick="set_qualify_friend()"/></td>
    </tr>
</table>