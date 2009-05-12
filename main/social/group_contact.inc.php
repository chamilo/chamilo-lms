<?php
$language_file = array('registration','messages','userInfo','admin');
require ('../inc/global.inc.php');
require_once (api_get_path(CONFIGURATION_PATH).'profile.conf.php');
include_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
include_once (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
include_once (api_get_path(LIBRARY_PATH).'image.lib.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once '../inc/lib/social.lib.php';
$this_section = SECTION_MYPROFILE;
$_SESSION['this_section']=$this_section;
$list_path_friends=array();
$list_groups=array();
$request=api_is_xml_http_request();
$language_variable=($request===true) ? api_convert_encoding(get_lang('ContactsGroups'),'UTF-8',$charset) : get_lang('ContactsGroups');
//api_display_tool_title($language_variable);
$user_id=api_get_user_id();
$list_groups=UserFriend::show_list_type_friends();

for ($p=0;$p<count($list_groups);$p++) {
	$list_path_friends=UserFriend::get_list_path_web_by_user_id ($user_id,$list_groups[$p]['id']);
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="data_table">

          <tr>
		<th align="left" id="<?php echo 'td_'.$list_groups[$p]['id']; ?>" style="cursor:pointer" valign="top" onclick="toogle_function(this)">
			<?php echo api_convert_encoding(get_lang($list_groups[$p]['title']),'UTF-8',$charset); ?>
		</th>
	    <th width="30" align="center">
    		<?php Display::display_icon('visible.gif',get_lang('ChangeVisibility'), array('id'=>'btn_'.$list_groups[$p]['id'])); ?>
    		<input type="hidden" class="hidden" id="id_hd_dame" name="hd_dame" value="0" />
	    </th>
        </tr>
      <tr>
	<td colspan="2">
		<div align="center" id="<?php echo 'div_group_'.$list_groups[$p]['id']; ?>" >
		<?php
		$friend_html='';
		$number_of_images=10;
		$number_friends=0;
		$list_friends_id=array();
		$list_friends_dir=array();
		$list_friends_file=array();
		if (count($list_path_friends)!=0) { 
	for ($z=0;$z<count($list_path_friends['id_friend']);$z++) {
		$list_friends_id[]  = $list_path_friends['id_friend'][$z]['friend_user_id'];
		$list_friends_dir[] = $list_path_friends['path_friend'][$z]['dir'];
		$list_friends_file[]= $list_path_friends['path_friend'][$z]['file'];
	}
	$number_friends= count($list_friends_dir);
	$number_loop   = ($number_friends/$number_of_images);
	$loop_friends  = ceil($number_loop);
	$j=0;//<div id="div_groupid_"'.$list_groups[$p]['id'].'">
	for ($k=0;$k<$loop_friends;$k++) {
		if ($j==$number_of_images) {
			$number_of_images=$number_of_images*2;
		}
		while ($j<$number_of_images) {
			if ($list_friends_file[$j]<>"") {
				$user_info=api_get_user_info($list_friends_id[$j]);				
				$user_name=api_convert_encoding($user_info['firstName'].' '.$user_info['lastName'],'UTF-8',$charset) ;
				if($list_friends_file[$j]==='unknown.jpg') {
					$big='';
				} else {
					$big='big_';
				}
				$friends_profile = UserFriend::get_picture_user($list_friends_id[$j], $list_friends_file[$j], 92);
				$friend_html.='<div id="div_'.$list_friends_id[$j].'" class="image_friend_network">' .
							  '<a href="javascript:void(0)" onclick=load_thick("'.$list_friends_dir[$j].$big.$list_friends_file[$j].'","") title="" class="thickbox">' .
							  '<span><center><img src="'.$friends_profile['file'].'" '.$friends_profile['style'].' id="imgfriend_'.$list_friends_id[$j].'" title="'.$user_name.'" /></center></span>'.
							  '<center class="friend">'.$user_name.'</center>'.
							  '</a></div>';
			}
			$j++;
		}
	}
	echo $friend_html; 
		}  
		else {
			echo api_convert_encoding(get_lang('YouDontHaveContactsInThisGroup'),'UTF-8',$charset);
		}
		?>
		</div>
	</td>
  </tr>
</table>
<?php
}
?>
