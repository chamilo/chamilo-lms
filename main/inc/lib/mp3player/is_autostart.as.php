<?php
/**
This script is used by the mp3 player to see if it should start automatically or not
*/

require ('../../global.inc.php');

switch($_SESSION['whereami'])
{


	case 'lp/build' :
	case 'document/create' :
	case 'document/edit' :
		$autostart = 'false';
	break;
	default :
		$autostart = 'true';

}
echo utf8_encode('autostart='.$autostart);
?>