<?php //$id: $
/**
 * Script handling the calls to the scorm class to reimport the imsmanifest of one LP
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Include mandatory libraries
 */
require_once('back_compat.inc.php');
require_once('learnpathItem.class.php');
require_once('learnpath.class.php');
require_once('scormItem.class.php');
require_once('scormResource.class.php');
require_once('scormMetadata.class.php');
require_once('scormOrganization.class.php');
require_once('scorm.class.php');

ini_set('max_execution_time',7200);

function my_get_time($time){
	$matches = array();
	if(preg_match('/(\d{2}):(\d{2}):(\d{2})(\.\d*)?/',$time,$matches)){
		return ($matches[1]*3600)+($matches[2]*60)+($matches[3]);
	}
	else return 0;
}

echo "<html><body>";

/**
 * New tables definition:
 */

//unique course to update
$lp_id = mysql_real_escape_string($_GET['lp']);
if(empty($lp_id)){die('No lp_id provided, sorry');}
else{
	scorm::reimport_manifest(api_get_course_id(),$lp_id);
}

echo "All done!";
echo "</body></html>";
?>
