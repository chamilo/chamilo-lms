<?php
// HTTP404 page with neat styling
// 2011, Jean-Karim Bockstael <jeankarim@cblue.be>
// ErrorDocument 404 /404/

$language_file = array('document', 'index');
require_once('../main/inc/global.inc.php');

$msg = get_lang('FileNotFound');
Display::display_header($msg);
Display::display_error_message($msg);
Display::display_footer();
?>
