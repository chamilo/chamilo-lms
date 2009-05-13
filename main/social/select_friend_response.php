<?php
$language_file = array('registration','messages','userInfo','admin');
require '../inc/global.inc.php';
include_once (api_get_path(LIBRARY_PATH).'image.lib.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
$this_section = SECTION_MYPROFILE;
$_SESSION['this_section']=$this_section;
api_block_anonymous_users();
$request=api_is_xml_http_request();
$language_variable=api_xml_http_response_encode(get_lang('PendingInvitations'));
$language_comment=api_xml_http_response_encode(get_lang('SocialInvitesComment'));
//api_display_tool_title($language_variable);
?>
<div id="id_response" align="center"></div>
<?php
$list_get_invitation=array();
$list_get_path_web=array();
$user_id=api_get_user_id();
$list_get_invitation=UserFriend::get_list_invitation_of_friends_by_user_id($user_id);
$list_get_path_web=UserFriend::get_list_web_path_user_invitation_by_user_id($user_id);
$number_loop=count($list_get_invitation);
if ($number_loop==0) {
	Display::display_normal_message(api_xml_http_response_encode(get_lang('YouDontHaveInvites')));
	
}
for ($i=0;$i<$number_loop;$i++) {
?>
<div id="<?php echo 'id_'.$list_get_invitation[$i]['user_sender_id'] ?>" align="center">
<table width="600" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="600" height="20" valign="top"><table width="100%" border="0"
cellpadding="0" cellspacing="0" bgcolor="#9DACBF">
      <tr>
        <td width="600" height="20" valign="top" style="padding:4px;"><div align="left"><b><?php echo api_xml_http_response_encode(get_lang('RequestContact')); ?></b></div></td>
        </tr>
    </table></td>
  </tr>
  <tr>
    <td height="135" valign="top"><table width="100%" border="0" cellpadding="0"
cellspacing="0">
      <tr>
        <td width="600" height="135" valign="top"><table width="100%" border="0"
cellpadding="0" cellspacing="0">
          <tr>
            <td width="600" height="110" valign="top"><table width="100%" border="0"
cellpadding="0" cellspacing="0">
              <tr>
                <td width="100" height="110" valign="top"><table width="100%"
border="0" cellpadding="0" cellspacing="0" bgcolor="#C8D5E4">
                  <tr>
                    <td width="100" height="110" style="padding:4px;" >
		                <?php $friends_profile = UserFriend::get_picture_user($list_get_invitation[$i]['user_sender_id'], $list_get_path_web[$i]['file'], 92); ?>
                    	<center><img src="<?php echo $friends_profile['file']; ?>" <?php echo $friends_profile['style']; ?> /></center></td>
                    </tr>
                  </table></td>
                      <td width="500" valign="top"><table width="100%" border="0"
cellpadding="0" cellspacing="0" bgcolor="#FFFFFF">
                          <tr>
                            <td width="500" height="22" valign="top">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                              <tr>
                                <td width="500" height="22" valign="top" style="padding:2px;">
                                <?php 
                                $user_id=$list_get_invitation[$i]['user_sender_id'];
                                $user_info=api_get_user_info($user_id);
                                echo api_xml_http_response_encode($user_info['firstName'].' '.$user_info['lastName']);
                                ?></td>
                                </tr>
                            </table></td>
                          </tr>
                          <tr>
                            <td height="5" valign="top"><table width="100%"
border="0" cellpadding="0" cellspacing="0">
                              
                              <tr>
                                <td width="500" height="5"></td>
                                </tr>
                            </table></td>
                          </tr>
                          <tr>
                            <td height="22" valign="top"><table width="100%"
border="0" cellpadding="0" cellspacing="0">
                              <tr>
                                <td width="500" height="22" valign="top" style="padding:2px;"><?php
                                $title=get_lang($list_get_invitation[$i]['title']);
                                $content=get_lang($list_get_invitation[$i]['content']);
                                echo api_xml_http_response_encode($title.' : '.$content);
                                ?> </td>
                                </tr>
                            </table></td>
                          </tr>
                          <tr>
                            <td height="61" valign="top" style="padding:2px;"><?php
                            $date=$list_get_invitation[$i]['send_date'];
                            echo api_xml_http_response_encode(get_lang('DateSend').' : '.$date);
                            ?></td>
                          </tr>
                      </table></td>
                    </tr>
            </table></td>
              </tr>
          <tr>
            <td height="25" valign="top"><table width="100%" border="0"
cellpadding="0" cellspacing="0" bgcolor="#9DACBF">
              <tr>
                <td width="600" height="25" valign="top" style="padding:4px;"><div align="right">
                  <input type="submit" name="btn_accepted" id="<?php echo "btn_accepted_".$user_id ?>" value="<?php echo api_xml_http_response_encode(get_lang('Accept')); ?>" onclick="javascript:register_friend(this)"  />
                  <input type="submit" name="btn_denied" id="<?php echo "btn_deniedst_".$user_id ?>" value="<?php echo api_xml_http_response_encode(get_lang('Deny')); ?>" onclick="javascript:denied_friend(this)" />
                  </div></td>
                    </tr>
            </table></td>
              </tr>
          
        </table></td>
        </tr>
      
    </table></td>
  </tr>
</table>
</div>
<br/>
<?php
}
?>
