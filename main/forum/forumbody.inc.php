<?php  // $Id:  $

$current_thread=get_thread_information($_GET['thread']);
$my_cid_req		 	= Security::remove_XSS($_GET['cidReq']);
$my_forum 			= Security::remove_XSS($_GET['forum']);
$my_thread 			= Security::remove_XSS($_GET['thread']);
$my_user_id 		= Security::remove_XSS($_GET['user_id']);
$user		 		= Security::remove_XSS($_GET['user']);
$my_idtextqualify   = isset($_REQUEST['idtextqualify']) ? Security::remove_XSS($_REQUEST['idtextqualify']) : $qualify;
$my_gradebook		= Security::remove_XSS($_GET['gradebook']);
$to_origin				= Security::remove_XSS($_GET['origin']);

$output = <<<FIN
<div class="forum-body-form">
 	<table>
 		<form id="forum-thread-qualify" name="forum-thread-qualify" action="forumqualify.php"> 		
 		<input type="hidden" name="cidReq" value="{$my_cid_req}">
 		<input type="hidden" name="forum" value="{$my_forum}">
 		<input type="hidden" name="thread" value="{$my_thread}">
 		<input type="hidden" name="user" value="{$user}">
 		<input type="hidden" name="user_id" value="{$my_user_id}">
 		<input type="hidden" name="gradebook" value="{$my_gradebook}">
  		<input type="hidden" name="origin" value="{$to_origin}">
    	<tr>
FIN;

$output .= '
			<td width="40%" class="forum-thread-header">'.get_lang('User').'&nbsp;:</td >
        	<td width="60%" class="forum-thread-body"><div align="left">'.get_name_user_by_id($userid).'</div></td>
        </tr>
        <tr>
    		<td width="40%" class="forum-thread-header">'.get_lang('Thread').'&nbsp;:</td >
            <td  width="60%" class="forum-thread-body">
                <div align="left">'.$current_thread['thread_title'].'</div>
            </td>
        </tr>

       	<tr>
    		<td width="40%" class="forum-thread-header">'.get_lang('CourseUsers').'&nbsp;:</td >
            <td  width="60%" class="forum-thread-body">
                <div align="left">'.$result['user_course'].'</div>
            </td>
        </tr>

		<tr>
    		<td width="40%" class="forum-thread-header">'.get_lang('PostsNumber').'&nbsp;:</td >
            <td  width="60%" class="forum-thread-body">
                <div align="left">'.$result['post'].'</div>
            </td>
        </tr>
                <tr>
    		<td width="40%" class="forum-thread-header">'.get_lang('NumberOfPostsForThisUser').'&nbsp;:</td >
            <td  width="60%" class="forum-thread-body">
                <div align="left">'.$result['user_post'].'</div>
            </td>
        </tr>
                <tr>
    		<td width="40%" class="forum-thread-header">'.get_lang('AveragePostPerUser').'&nbsp;:</td >
            <td  width="60%" class="forum-thread-body">
                <div align="left">'.round($result['user_post']/$result['post'],2).'</div>
            </td>
        </tr>
        <tr>
            <td width="40%" class="forum-thread-header"><div align="left">'.get_lang('Qualification').'&nbsp;:</div></td >
            <td  width="60%" class="forum-thread-body"><div align="left">
            	<input type="text" maxlength="8" id="idtextqualify" style="width:60px;" name="idtextqualify" value="'.$my_idtextqualify.'" />&nbsp;&nbsp;'.get_lang('MaxScore').'&nbsp;: '.$max_qualify.'</div>
            </td>
        </tr>
    	<tr>
            <td width="40%"></td>
            <td width="60%"><div align="left"><input type="button" id="idbutton_qualify" name="idbutton_qualify"  value="'.get_lang('QualifyThisThread').'" onclick="javascript:if(document.getElementById(\'idtextqualify\').value>=0){if(confirm(\''.get_lang('ConfirmUserQualification').'\')){document.getElementById(\'forum-thread-qualify\').submit();}else{return false;};}else{alert(\''.get_lang('InsertQualificationCorrespondingToMaxScore').'\')};" /></div></td>
        </tr>
        </form>
    </table>
</div>';
echo $output;