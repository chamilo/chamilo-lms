<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Script that displays a blank page (with later a message saying why).
 *
 * @package chamilo.learnpath
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';
$htmlHeadXtra[] = "
<style>
body { background: none;}
</style>
";

$message = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'document_deleted':
            $message = Display::return_message(get_lang('The document cannot be displayed because it has been deleted'), 'error');
            break;
        case 'prerequisites':
            $prerequisiteMessage = isset($_GET['prerequisite_message']) ? $_GET['prerequisite_message'] : '';
            $message = Display::return_message(get_lang('This learning object cannot display because the course prerequisites are not completed. This happens when a course imposes that you follow it step by step or get a minimum score in tests before you reach the next steps.'), 'warning');
            if (!empty($prerequisiteMessage)) {
                $message = Display::return_message(Security::remove_XSS($prerequisiteMessage), 'warning');
            }

            break;
        case 'document_not_found':
            $message = Display::return_message(get_lang('The file was not found'), 'warning');
            break;
        case 'reached_one_attempt':
            $message = Display::return_message(get_lang('You can not take this test because you have already reached one attempt'), 'warning');
            break;
        case 'x_frames_options':
            $src = Session::read('x_frame_source');
            if (!empty($src)) {
                $icon = '<em class="icon-play-sign icon-2x" aria-hidden="true"></em>';
                $message = Display::return_message(
                    Display::url(
                        $icon.$src,
                        $src,
                        ['class' => 'btn generated', 'target' => '_blank']
                    ),
                    'normal',
                    false
                );
                Session::erase('x_frame_source');
            }
            break;
        default:
            break;
    }
} elseif (isset($_GET['msg']) && $_GET['msg'] == 'exerciseFinished') {
    $message = Display::return_message(get_lang('Test Finished'));
}

$template = new Template();
$template->assign('content', $message);
$template->display_blank_template();
