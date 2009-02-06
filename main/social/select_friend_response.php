<?php
$language_file = array('registration','messages');
$cidReset = true;
require '../inc/global.inc.php';
include_once (api_get_path(LIBRARY_PATH).'image.lib.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
$this_section = SECTION_MYPROFILE;
$_SESSION['this_section']=$this_section;
api_block_anonymous_users();
?>
<div class="actions">
<?php echo get_lang('ShowMessageInvitation'); ?>
</div>
<div id="id_response" align="center"></div>
<?php
$list_get_invitation=array();
$list_get_path_web=array();
$user_id=api_get_user_id();
$list_get_invitation=UserFriend::get_list_invitation_of_friends_by_user_id($user_id);
$list_get_path_web=UserFriend::get_list_web_path_user_invitation_by_user_id($user_id);
$number_loop=count($list_get_invitation);
if ($number_loop==0) {
	echo Display::display_normal_message(get_lang('NoHaveInvitation'));
	
}
for ($i=0;$i<$number_loop;$i++) {
?>
<div id="<?php echo 'id_'.$list_get_invitation[$i]['user_sender_id'] ?>" align="center">
<table width="600" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="600" height="20" valign="top"><table width="100%" border="0"
cellpadding="0" cellspacing="0" bgcolor="#9DACBF">
      <tr>
        <td width="600" height="20" valign="top"><div align="left"><?php echo get_lang('RequestFriend'); ?></div></td>
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
                    <td width="100" height="110" valign="top">
                    <img src="<?php echo $list_get_path_web[$i]['dir']."/".$list_get_path_web[$i]['file']; ?>" width="90" height="100" style="margin-left:5px ;margin-rigth:5px;margin-top:5px;margin-bottom:5px;" /></td>
                          </tr>
                  
                  </table></td>
                      <td width="500" valign="top"><table width="100%" border="0"
cellpadding="0" cellspacing="0" bgcolor="#FFFFFF">
                          <tr>
                            <td width="500" height="22" valign="top"><table
width="100%" border="0" cellpadding="0" cellspacing="0">
                              
                              <tr>
                                <td width="500" height="22" valign="top">
                                <?php 
                                $user_id=$list_get_invitation[$i]['user_sender_id'];
                                $user_info=api_get_user_info($user_id);
                                echo $user_info['firstName'].' '.$user_info['lastName'];
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
                                <td width="500" height="22" valign="top"><?php
                                $title=$list_get_invitation[$i]['title'];
                                $content=$list_get_invitation[$i]['content'];
                                echo $title.' '.$content;
                                ?> </td>
                                </tr>
                            </table></td>
                          </tr>
                          <tr>
                            <td height="61" valign="top"><?php
                            $date=$list_get_invitation[$i]['send_date'];
                            echo get_lang('DateSent').' : '.$date;
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
                <td width="600" height="25" valign="top"><div align="right">
                  <input type="submit" name="btn_accepted" id="<?php echo "btn_accepted_".$user_id ?>" value="<?php echo get_lang('Accept'); ?>" onclick="javascript:register_friend(this)"  />
                  <input type="submit" name="btn_denied" id="<?php echo "btn_deniedst_".$user_id ?>" value="<?php echo get_lang('Deny'); ?>" onclick="javascript:denied_friend(this)" />
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