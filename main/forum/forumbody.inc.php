<?php  // $Id:  $

$current_thread=get_thread_information($_GET['thread']);
$my_cid_req		 	= Security::remove_XSS($_GET['cidReq']);
$my_forum 			= Security::remove_XSS($_GET['forum']);
$my_thread 			= Security::remove_XSS($_GET['thread']);
$my_user_id 		= Security::remove_XSS($_GET['user_id']);
$my_idtextqualify   = isset($_REQUEST['idtextqualify']) ? Security::remove_XSS($_REQUEST['idtextqualify']) : $qualify;
$my_gradebook		= Security::remove_XSS($_GET['gradebook']);
$output = <<<FIN
<div class="forum-body-form">
 	<table>
 		<form id="forum-thread-qualify" name="forum-thread-qualify" action="forumqualify.php"> 		
 		<input type="hidden" name="cidReq" value="{$my_cid_req}">
 		<input type="hidden" name="forum" value="{$my_forum}">
 		<input type="hidden" name="thread" value="{$my_thread}">
 		<input type="hidden" name="user_id" value="{$my_user_id}">
 		<input type="hidden" name="gradebook" value="{$my_gradebook}">
    	<tr>
FIN;
$output .= '
			<td width="10%" class="forum-thread-header">'.get_lang('User').'&nbsp;:</td >
        	<td width="90%" class="forum-thread-body"><div align="left">'.get_name_user_by_id($userid).'</div></td>
        </tr>
        <tr>
    		<td width="10%" class="forum-thread-header">'.get_lang('Thread').'&nbsp;:</td >
            <td  width="90%" class="forum-thread-body">
                <div align="left">'.$current_thread['thread_title'].'</div>
            </td>
        </tr>
        <tr>
            <td width="10%" class="forum-thread-header"><div align="left">'.get_lang('Qualification').'&nbsp;:</div></td >
            <td  width="90%" class="forum-thread-body"><div align="left">
            	<input type="text" maxlength="4" id="idtextqualify" style="width:40px;" name="idtextqualify" value="'.$my_idtextqualify.'" />&nbsp;&nbsp;'.get_lang('MaxScore').'&nbsp;: '.$max_qualify.'</div>
            </td>
        </tr>
    	<tr>
            <td width="10%"></td>
            <td width="90%"><div align="left"><input type="button" id="idbutton_qualify" name="idbutton_qualify"  value="'.get_lang('QualifyThisThread').'" onclick="javascript:if(document.getElementById(\'idtextqualify\').value>=0){if(confirm(\''.get_lang('ConfirmUserQualification').'\')){document.getElementById(\'forum-thread-qualify\').submit();}else{return false;};}else{alert(\''.get_lang('InsertQualificationCorrespondingToMaxScore').'\')};" /></div></td>
        </tr>
        </form>
    </table>
</div>';
echo $output;