<?php 
$language_file=array('registration','messages','userInfo','admin');
require_once '../inc/global.inc.php';
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once '../inc/lib/social.lib.php';
$user_friend=$_POST['user_friend'];
$user_info=api_get_user_info($user_friend);
$list_of_options=array();
$img_user=array();
$img_info_user=array();
$list_of_options=UserFriend::show_list_type_friends();
$path_user=str_replace('\\','',$_GET['path_user']);
$img_user =explode('"',$path_user);
$number_list=count($list_of_options);
$user_id  =urldecode($_GET['id_user']);
$user_id  =str_replace("\\","",$user_id);
$user_friend=str_replace('"',"",$user_id);
$user_friend_relation=UserFriend::get_relation_between_contacts(api_get_user_id(),$user_friend);
?>
<input type="hidden"  name="user_cod_qualify" id="user_cod_qualify" value="<?php echo $user_friend; ?>"/>
<table width="600" border="0" cellspacing="0" cellpadding="0">
<tr>
<td class="actions"><?php echo mb_convert_encoding(get_lang('SocialAttachUserInformation'),'UTF-8',$charset);?></td>
</tr>
<tr>
<td>
<table width="600" border="0" cellspacing="0" cellpadding="0">
	<tr>
	   	 <td width="600" align="left">
	   	 	<td width="50%"><br/>
	   	 		<img style="float:left" src="<?php echo $img_user[1]; ?>" />
	   	 	</td>
	   	 <td width="50%"><div align="left">
<?php
for ($k=0;$k<$number_list;$k++) {
	   echo '<br/>';
   if ($list_of_options[$k]['id']==$user_friend_relation) {
   		$check='checked="checked"';
   } else {
   		$check='';
}
?>
					    
<input <?php echo $check; ?> style="margin-left:50px" type="radio" class="radio" name="list_type_friend"  value="<?php echo $list_of_options[$k]['id']; ?>" />
<?php 
echo  mb_convert_encoding(get_lang($list_of_options[$k]['title']),'UTF-8',$charset); 
echo '<br/>';
?>
<?php
			}
echo '<br/>';
?>
<input  style="margin-left:50px" type="button" value="<?php echo mb_convert_encoding(get_lang('AttachToGroup'),'UTF-8',$charset); ?>" onclick="set_qualify_friend()"/>
			</div></td>
			</td>
		</tr>
	</table>

</td>
</tr>
</table>