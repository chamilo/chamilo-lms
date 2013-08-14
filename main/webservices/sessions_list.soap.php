<?php
/* For licensing terms, see /license.txt */
/**
 * This script provides the caller service with a list
 * of sessions that have a certain availability period
 * on this chamilo portal.
 * It is set to work with the Chamilo module for Drupal:
 * http://drupal.org/project/chamilo
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @package chamilo.webservices
 */
require_once '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'nusoap/nusoap.php';

// Create the server instance
$server = new soap_server();
// Initialize WSDL support
$server->configureWSDL('WSSessionList', 'urn:WSSessionList');

/* Register WSCourseList function */
// Register the data structures used by the service

$server->wsdl->addComplexType(
        'sessionDetails',
        'complexType',
        'struct',
        'all',
        '',
        array(
          'name'=>'id'  , 'type'=>'xsd:string',
          'name'=>'title'  , 'type'=>'xsd:string',
          'name'=>'url'    , 'type'=>'xsd:string',
          'name'=>'date_start', 'type'=>'xsd:string',
          'name'=>'date_end','type'=>'xsd:string',
        )
);

$server->wsdl->addComplexType(
    'sessionList',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP:ENC:arrayType',
        'wsdl:arrayType'=>'tns:sessionDetails[]')
    ),
    'tns:sessionDetails'
);

// Register the method to expose
$server->register('WSSessionList',         // method name
    array('username' => 'xsd:string',
          'signature' => 'xsd:string',
          'date_start' => 'xsd:string',
          'date_end' => 'xsd:string'),      // input parameters
    array('return' => 'xsd:Array'),             // output parameters
    'urn:WSSessionList',                         // namespace
    'urn:WSSessionList#WSSessionList',      // soapaction
    'rpc',                                      // style
    'encoded',                                  // use
    'This service returns a list of sessions'    // documentation
);


/**
 * Get a list of sessions (id, title, url, date_start, date_end) and 
 * return to caller
 * Function registered as service. Returns strings in UTF-8.
 * @param string User name in Chamilo
 * @param string Signature (composed of the sha1(username+apikey)
 * @param string Date which sessions have to *start after* to be returned
 * @param string Date which sessions have to *end before* to be returned
 * @return array Sessions list (id=>[title=>'title',url='http://...',date_start=>'...',date_end=>''])
 */
function WSSessionList($username, $signature, $date_start = null, $date_end = null) {
    if (empty($username) or empty($signature)) { return -1; }

    global $_configuration;

    $info = api_get_user_info_from_username($username);
    $user_id = $info['user_id'];
    if (!UserManager::is_admin($user_id)) { return -1; }

    $list = UserManager::get_api_keys($user_id, 'dokeos');
    $key = '';
    //get the last API key
    foreach ($list as $key) {
        break;
    }

    $local_key = $username.$key;

    if (!api_is_valid_secret_key($signature, $local_key)) {
        return -1; // The secret key is incorrect.
    }
    $params = array();
    // Dates should be provided in YYYY-MM-DD format, UTC
    if (!empty($date_start)) {
        $params['date_start >='] = $date_start;
    }
    if (!empty($date_end)) {
        $params['date_end <='] = $date_end;
    }
    $sessions_list = SessionManager::get_sessions_list($params);
    return $sessions_list;
}

// Use the request to (try to) invoke the service.
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
