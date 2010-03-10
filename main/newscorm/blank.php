<?php //$id: $
/* For licensing terms, see /license.txt */
/**
 * Script that displays a blank page (with later a message saying why)
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

$language_file = array('learnpath','document');

//flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;

require_once '../inc/global.inc.php';
require_once '../inc/reduced_header.inc.php';
?>
<body>
<?php
if (isset($_GET['error'])) {
	switch($_GET['error']){
		case 'document_deleted':
			echo '<br /><br />';
			Display::display_error_message(get_lang('DocumentHasBeenDeleted'));
			break;
		case 'prerequisites':
			echo '<br /><br />';
			Display::display_normal_message(get_lang('_prereq_not_complete'));
			break;
		case 'document_not_found':
			echo '<br /><br />';
			Display::display_normal_message(get_lang('FileNotFound'));
			break;
		default:
			break;
	}
} else if(isset($_GET['msg']) && $_GET['msg']=='exerciseFinished') {
	echo '<br /><br />';
	Display::display_normal_message(get_lang('ExerciseFinished'));
}
?>
</body>
</html>