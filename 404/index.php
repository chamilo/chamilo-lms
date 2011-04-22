<?php
// HTTP404 page with neat styling
// 2011, Jean-Karim Bockstael <jeankarim@cblue.be>
// ErrorDocument 404 /404/

$language_file = array('document', 'index');
require_once('../main/inc/global.inc.php');

Display::display_header("File not found");
echo '<p>'.get_lang('FileNotFound').'</p>';
Display::display_footer();
?>
