<?php /* For licensing terms, see /license.txt */
/**
 * Test script for soap.php
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @package chamilo.webservices
 */
/**
 * Init
 */
exit; //remove to enable
// Include the necessary files, assuming this script is located in main/newscorm/ or something like that
require_once '../inc/global.inc.php';
global $_configuration;

// First build the signature to use with the webservice. We assume
// we are calling the webservice from the same server, so getting
// the IP (part of the signature) can be done through $_SERVER['REMOTE_ADDR']
$ip = trim($_SERVER['REMOTE_ADDR']);
$signature = sha1($ip.$_configuration['security_key']);

// Prepare the arguments to the webservice, based on the user ID (int), the course ID (int), the learnpath_id and the learnpath_item_id:
$uid = 1; // set to your user ID
$cid = 1; // set to your course ID
$lpid = 1; // set to your learnpath ID
$lpiid = 1; // set to your learnpath item ID

// Build the server's SOAP script address
$server = api_get_path(WEB_CODE_PATH).'webservices/soap.php?wsdl';

/**
 * Call the webservice
 */

// Init the SOAP connection
$client = new SoapClient($server, array('cache_wsdl' => WSDL_CACHE_NONE));

// Call the function we want with the right params...
$response = $client->{'WSReport.test'}();
//$response = $client->{'WSReport.GetLearnpathStatusSingleItem'}($signature, 'chamilo_user_id', $uid, 'chamilo_course_id', $cid, $lpid, $lpiid);
//$response = $client->{'WSReport.GetLearnpathProgress'}($signature, 'chamilo_user_id', $uid, 'chamilo_course_id', $cid, $lpid);
//$response = $client->{'WSReport.GetLearnpathHighestLessonLocation'}($signature, 'chamilo_user_id', $uid, 'chamilo_course_id', $cid, $lpid);
// Print the output, or do whatever you like with it (it's the status for this item):
echo '<pre>'.$response.'</pre>';
// This should print "complete", "incomplete" or any other active status.