<?php
$cidReset = true;
require ('../inc/global.inc.php');
$this_section = SECTION_MYPROFILE;
$_SESSION['this_section']=$this_section;
$language_file = array('registration','messages');
require_once (api_get_path(CONFIGURATION_PATH).'profile.conf.php');
include_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
include_once (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
include_once (api_get_path(LIBRARY_PATH).'image.lib.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once '../inc/lib/social.lib.php';
//$list_path_friends=array();
?>
<div id="id" class="actions">
<?php echo get_lang('MessageInformationContacts') ?>
</div>
<?php
$user_id=api_get_user_id();
$image_path = UserManager::get_user_picture_path_by_id ($user_id,'web',false,true);
?>
<div align="center" >
<table width="750" border="0" cellpadding="0" cellspacing="0" style="border-top:1px #9DACBF solid; border-left:1px #9DACBF solid;border-right:1px #9DACBF solid; border-bottom:1px #9DACBF solid">
  <tr>
    <td width="750" height="20" valign="top">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="social-title">
      <tr>
        <td width="750" height="20" valign="top"><?php
        echo '&nbsp;&nbsp;Dokeos&nbsp;&nbsp;-&nbsp;&nbsp;';
        $user_id=api_get_user_id();
        $user_info=api_get_user_info($user_id);
        echo $user_info['firstName'].'&nbsp;&nbsp;'.$user_info['lastName'];
         ?></td>
        </tr>
    </table>
    </td>
  </tr>
  <tr>
    <td height="180" valign="top">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" >
      
      <tr>
        <td width="320" height="180" valign="top">
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="320" height="110" valign="top">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
              
              <tr>
                <td width="100" height="110" valign="top">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#ffffff">
                  
                  <tr>
                    <td width="100" height="110" valign="top">
                    <img src="<?php echo $image_path['dir']."/".$image_path['file']; ?>" width="90" height="100" style="margin-left:5px ;margin-rigth:5px;margin-top:5px;margin-bottom:5px;" /></td>
                   </tr>
                  
                </table></td>
                    <td width="220" valign="top">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="social-align-box">
                      <tr>
                        <td width="220" height="110" valign="top">&nbsp;</td>
                        </tr>
                      
                    </table></td>
                    </tr>
            </table></td>
              </tr>
          <tr>
            <td height="70" valign="top">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" class="social-align-box">
              <tr>
                <td width="320" height="70" valign="top" class="social-info">
                <?php
                $info_user=api_get_user_info($user_id);
                 echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;'.$info_user['mail'].'<br/>';
                 echo '&nbsp;&nbsp;&nbsp;&nbsp;'.$info_user['username'].PHP_EOL;
                 ?>
                </td>
                </tr>
            </table></td>
            </tr>
        </table></td>
        <td width="430" valign="top">
        <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#ECE9D8" class="social-align-box">
          <tr>
            <td width="430" height="180" valign="top" >
            <div align="center" class="social-qualify"><?php echo get_lang('GroupingMyContactsPersonal') ?><input type="hidden" class="" name="user_cod_qualify" id="user_cod_qualify" value="0" /></div>
            <div align="center" class="social-qualify-display" id="div_info_user"></div>
            <div id="div_qualify">
            <?php 
            	require_once 'qualify_contact.inc.php';
            ?>
            <div id="div_qualify_image" class="social-display-image"></div>
            </div>
            </td>
              </tr>
          
        </table></td>
        </tr>
    </table></td>
  </tr>
  <tr>
    <td height="25" valign="top">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="social-subtitle-search">
      <tr>
        <td width="750" height="25" valign="top" class="social-align-box">&nbsp;&nbsp;<?php echo get_lang('Search').'&nbsp;&nbsp; : &nbsp;&nbsp;'; ?><input class="social-search-image" type="text" class="search-image" id="id_search_image" name="id_search_image" value="" onkeyup="search_image_social(this)" /></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="175" valign="top">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" >
      <tr>
        <td width="750" height="22" valign="top">
        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="social-align-box">
          <tr>
            <td width="750" height="22" valign="top" class="social-title">&nbsp;&nbsp;<?php echo get_lang('ListContacts'); ?></td>
              </tr>
        </table></td>
        </tr>
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