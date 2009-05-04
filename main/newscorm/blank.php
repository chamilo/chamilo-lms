<?php //$id: $
/**
 * Script that displays a blank page (with later a message saying why)
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Script
 */
 

$language_file[] = "learnpath";

//flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;

require('../inc/global.inc.php');
include_once('../inc/reduced_header.inc.php');

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