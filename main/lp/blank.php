<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Script that displays a blank page (with later a message saying why)
 * @package chamilo.learnpath
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

$message = null;

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'document_deleted':
            $message = Display::return_message(get_lang('DocumentHasBeenDeleted'), 'error');
            break;
        case 'prerequisites':
            $message = Display::return_message(get_lang('LearnpathPrereqNotCompleted'), 'warning');
            break;
        case 'document_not_found':
            $message = Display::return_message(get_lang('FileNotFound'), 'warning');
            break;
        case 'reached_one_attempt':
            $message = Display::return_message(get_lang('ReachedOneAttempt'), 'warning');
            break;
        case 'x_frames_options':
            $src = Session::read('x_frame_source');
            if (!empty($src)) {
                $icon = '<em class="icon-play-sign icon-2x" aria-hidden="true"></em> ';

                $message = Display::return_message(
                    Display::url($icon.$src, $src, ['class' => 'btn generated', 'target' => '_blank']),
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
    Display::addFlash(
        Display::return_message(get_lang('ExerciseFinished'))
    );
}

if (!empty($message)) {
    Display::addFlash($message);
}

Display::display_reduced_header();
Display::display_reduced_footer();
?>
