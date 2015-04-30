<?php
/* For licensing terms, see /license.txt */

$current_thread = get_thread_information($_GET['thread']);

$my_cid_req = Security::remove_XSS($_GET['cidReq']);
$my_forum = intval($_GET['forum']);
$my_thread = intval($_GET['thread']);
$my_user_id = intval($_GET['user_id']);

// Show current qualify in my form
$qualify = current_qualify_of_thread(
    $_GET['thread'],
    api_get_session_id(),
    api_get_user_id()
);

$user = intval($_GET['user']);
$my_gradebook = Security::remove_XSS($_GET['gradebook']);
$to_origin = isset($_GET['origin']) ? Security::remove_XSS($_GET['origin']) : '';
$url = api_get_path(WEB_CODE_PATH).'forum/forumqualify.php?'.
       api_get_cidreq().'&forum='.$my_forum.'&thread='.$my_thread.'&user='.$user.'&user_id='.$user;
$output = '
<div class="forum-body-form">
     <table class="table">
         <form
            id="forum-thread-qualify"
            method="post"
            name="forum-thread-qualify"
            action="'.$url.'"
         >
        <tr>
';

$output .= '
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
                <div align="left">'.round($result['user_post']/$result['post'], 2).'</div>
            </td>
        </tr>
        <tr>
            <td width="40%" class="forum-thread-header"><div align="left">'.get_lang('Qualification').'&nbsp;:</div></td >
            <td  width="60%" class="forum-thread-body"><div align="left">
                <input type="text" maxlength="8" id="idtextqualify" style="width:60px;" name="idtextqualify" value="'.$qualify.'" />&nbsp;&nbsp;'.
                get_lang('MaxScore').'&nbsp;: '.$max_qualify.'</div>
            </td>
        </tr>
        <tr>
            <td width="40%"></td>
            <td width="60%">
                <div align="left">
                    <button type="button" class="btn btn-primary" id="idbutton_qualify" name="idbutton_qualify" value="'.get_lang('QualifyThisThread').'" onclick="javascript:if(document.getElementById(\'idtextqualify\').value>=0){if(confirm(\''.get_lang('ConfirmUserQualification').'\')){document.getElementById(\'forum-thread-qualify\').submit();}else{return false;};}else{alert(\''.get_lang('InsertQualificationCorrespondingToMaxScore').'\')};" >
                    '.get_lang('QualifyThisThread').'
                    </button>
                </div>
            </td>
        </tr>
        </form>
    </table>
</div>';
echo $output;
