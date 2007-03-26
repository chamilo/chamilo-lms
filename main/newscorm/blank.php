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
require('../inc/global.inc.php');
include_once('../inc/reduced_header.inc.php');

?>

<body>

<?php

switch($_GET['error']){
	case 'document_deleted':
		Display::display_error_message(get_lang('DocumentHasBeenDeleted'));
		break;
	case 'prerequisites':
		Display::display_normal_message(get_lang('_prereq_not_complete'));
		break;
	default:
		break;
}

?>

</body>
</html>