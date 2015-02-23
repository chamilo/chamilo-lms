<?php
/* For licensing terms, see /license.txt */

/**
 *	Allows to type the messages that will be displayed on chat_chat.php
 *
 *	@author Olivier Brouckaert
 * 	Modified by Alex Aragón (BeezNest)
 *	@package chamilo.chat
 */
define('FRAME', 'message');

require_once '../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'chat/chat_functions.lib.php';

$userId = api_get_user_id();
$userInfo = api_get_user_info();
$course = api_get_course_id();
$session_id = api_get_session_id();
$group_id = api_get_group_id();
$_course = api_get_course_info();

// Juan Carlos Raña inserted smileys and self-closing window.
?>
<script>
function close_chat_window() {
    var chat_window = top.window.self;
    chat_window.opener = top.window.self;
    chat_window.top.close();
}
</script>
<?php

// Mode open in a new window: close the window when there isn't an user login
if (empty($userId)) {
    echo '<script languaje="javascript" type="text/javascript"> close_chat_window(); </script>';
} else {
    api_protect_course_script();
}

if (empty($course) || empty($userId)) {
    exit;
}

/*	Constants and variables */
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$sent = isset($_REQUEST['sent']) ? $_REQUEST['sent'] : null;

require 'header_frame.inc.php';
$chat_size = 0;

if ($sent) {
    saveMessage(
        $_POST['message'],
        $userId,
        $_course,
        $session_id,
        $group_id,
        false
    );
}
?>
<form
    id="formMessage"
    name="formMessage"
    method="post"
    action="<?php echo api_get_self().'?'.api_get_cidreq(); ?>" onsubmit="javascript: if(document.formMessage.message.value == '') { alert('<?php echo addslashes(api_htmlentities(get_lang('TypeMessage'), ENT_QUOTES)); ?>'); document.formMessage.message.focus(); return false; }"
    autocomplete="off"
>
    <input type="hidden" name="sent" value="1">
    <div class="message-form-chat">
        <div class="tabbable">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="#tab1" data-toggle="tab">
                        <?php echo get_lang('Write'); ?>
                    </a>
                </li>
                <li>
                    <a href="#tab2" id="preview" data-toggle="tab">
                        <?php echo get_lang('Preview'); ?>
                    </a>
                </li>
                <li>
                    <a href="#tab3" id="emojis" data-toggle="tab">
                        <?php echo Emojione\Emojione::toImage(':smile:'); ?>
                    </a>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="tab1">
                    <table border="0" cellpadding="5" cellspacing="0" width="100%">
                        <tr>
                            <td width="320" valign="middle">
                                <?php
                                $talkboxsize = (api_get_course_setting('allow_open_chat_window')) ? 'width: 350px; height: 80px' : 'width: 450px; height: 35px';
                                ?>
                                <textarea id="message" class="message-text" name="message" style=" <?php echo $talkboxsize; ?>"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="btn-group">
                                    <button id="send" type="submit" value="<?php echo get_lang('Send'); ?>" class="btn btn-primary">
                                        <?php echo get_lang('Send'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="tab-pane" id="tab2">
                    <table border="0" cellpadding="5" cellspacing="0" width="100%">
                        <tr>
                            <td width="320" valign="middle">
                                <div id="html-preview" class="emoji-wysiwyg-editor-preview">
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </form>
<?php

require 'footer_frame.inc.php';
