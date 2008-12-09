<?php
/**
 * OpenID login method
 * 
 * The OpenID login method relies on authentication servers providing a public
 * URL that can confirm the identity of a person, thus avoiding the spread
 * use of password transmissions over non-secure lines (for Dokeos, it is a
 * good way of avoiding password theft)
 */
/**
 * Initialisation
 */
require_once('openid.conf.php');
require_once('openid.lib.php');
require_once('xrds.lib.php');

function openid_form() 
{
	return '<div class="menusection"><span class="menusectioncaption">'.get_lang('OpenIdAuthentication').'</span><form name="openid_login" method="post"><label for="openid_url">'.get_lang('OpenIDURL').' <a href="main/auth/openid/whatis.php" title="'.get_lang('OpenIDWhatIs').'">'.Display::return_icon('info3.gif').'</a></label><input type="text" id="openid_url" name="openid_url" style="background: url(main/img/openid_small_logo.png) no-repeat; background-color: #fff; background-position: 0 50%; padding-left:18px;" value="http://"></input><input type="submit" name="openid_login" value="'.get_lang('Ok').'" /><br /><br /></form></div>';
}

/**
 * The initial step of OpenID authentication responsible for the following:
 *  - Perform discovery on the claimed OpenID.
 *  - If possible, create an association with the Provider's endpoint.
 *  - Create the authentication request.
 *  - Perform the appropriate redirect.
 *
 * @param $claimed_id The OpenID to authenticate
 * @param $return_to The endpoint to return to from the OpenID Provider
 */
function openid_begin($claimed_id, $return_to = '', $form_values = array()) 
{

  $claimed_id = _openid_normalize($claimed_id);

  $services = openid_discovery($claimed_id);
  if (count($services) == 0) {
    echo 'Sorry, that is not a valid OpenID. Please ensure you have spelled your ID correctly.';
    return;
  }

  $op_endpoint = $services[0]['uri'];
  // Store the discovered endpoint in the session (so we don't have to rediscover).
  $_SESSION['openid_op_endpoint'] = $op_endpoint;
  // Store the claimed_id in the session (for handling delegation).
  $_SESSION['openid_claimed_id'] = $claimed_id;
  // Store the login form values so we can pass them to
  // user_exteral_login later.
  $_SESSION['openid_user_login_values'] = $form_values;

  // If bcmath is present, then create an association
  $assoc_handle = '';
  if (function_exists('bcadd')) {
    $assoc_handle = openid_association($op_endpoint);
  }

  // Now that there is an association created, move on
  // to request authentication from the IdP
  $identity = (!empty($services[0]['delegate'])) ? $services[0]['delegate'] : $claimed_id;
  if (isset($services[0]['types']) && is_array($services[0]['types']) && in_array(OPENID_NS_2_0 .'/server', $services[0]['types'])) {
    $identity = 'http://openid.net/identifier_select/2.0';
  }
  $authn_request = openid_authentication_request($claimed_id, $identity, $return_to, $assoc_handle, $services[0]['version']);

  if ($services[0]['version'] == 2) {
    openid_redirect($op_endpoint, $authn_request);
  }
  else {
    openid_redirect_http($op_endpoint, $authn_request);
  }
}

/**
 * Completes OpenID authentication by validating returned data from the OpenID
 * Provider.
 *
 * @param $response Array of returned from the OpenID provider (typically $_REQUEST).
 *
 * @return $response Response values for further processing with
 *   $response['status'] set to one of 'success', 'failed' or 'cancel'.
 */
function openid_complete($response) 
{
  // Default to failed response
  $response['status'] = 'failed';
  if (isset($_SESSION['openid_op_endpoint']) && isset($_SESSION['openid_claimed_id'])) {
    _openid_fix_post($response);
    $op_endpoint = $_SESSION['openid_op_endpoint'];
    $claimed_id = $_SESSION['openid_claimed_id'];
    unset($_SESSION['openid_op_endpoint']);
    unset($_SESSION['openid_claimed_id']);
    if (isset($response['openid.mode'])) {
      if ($response['openid.mode'] == 'cancel') {
        $response['status'] = 'cancel';
      }
      else {
        if (openid_verify_assertion($op_endpoint, $response)) {
          $response['openid.identity'] = $claimed_id;
          $response['status'] = 'success';
        }
      }
    }
  }
  return $response;
}

/**
 * Perform discovery on a claimed ID to determine the OpenID provider endpoint.
 *
 * @param $claimed_id The OpenID URL to perform discovery on.
 *
 * @return Array of services discovered (including OpenID version, endpoint
 * URI, etc).
 */
function openid_discovery($claimed_id) {

  $services = array();

  $xrds_url = $claimed_id;
  if (_openid_is_xri($claimed_id)) {
    $xrds_url = 'http://xri.net/'. $claimed_id;
  }
  $url = @parse_url($xrds_url);
  if ($url['scheme'] == 'http' || $url['scheme'] == 'https') {
    // For regular URLs, try Yadis resolution first, then HTML-based discovery
    $headers = array('Accept' => 'application/xrds+xml');
    //TODO
    $result = openid_http_request($xrds_url, $headers);

    if (!isset($result->error)) {
      if (isset($result->headers['Content-Type']) && preg_match("/application\/xrds\+xml/", $result->headers['Content-Type'])) {
        // Parse XML document to find URL
        $services = xrds_parse($result->data);
      }
      else {
        $xrds_url = NULL;
        if (isset($result->headers['X-XRDS-Location'])) {
          $xrds_url = $result->headers['X-XRDS-Location'];
        }
        else {
          // Look for meta http-equiv link in HTML head
          $xrds_url = _openid_meta_httpequiv('X-XRDS-Location', $result->data);
        }
        if (!empty($xrds_url)) {
          $headers = array('Accept' => 'application/xrds+xml');
          //TODO
          $xrds_result = openid_http_request($xrds_url, $headers);
          if (!isset($xrds_result->error)) {
            $services = xrds_parse($xrds_result->data);
          }
        }
      }

      // Check for HTML delegation
      if (count($services) == 0) {
        // Look for 2.0 links
        $uri = _openid_link_href('openid2.provider', $result->data);
        $delegate = _openid_link_href('openid2.local_id', $result->data);
        $version = 2;

        // 1.0 links
        if (empty($uri)) {
          $uri = _openid_link_href('openid.server', $result->data);
          $delegate = _openid_link_href('openid.delegate', $result->data);
          $version = 1;
        }
        if (!empty($uri)) {
          $services[] = array('uri' => $uri, 'delegate' => $delegate, 'version' => $version);
        }
      }
    }
  }
  return $services;
}

/**
 * Attempt to create a shared secret with the OpenID Provider.
 *
 * @param $op_endpoint URL of the OpenID Provider endpoint.
 *
 * @return $assoc_handle The association handle.
 */
function openid_association($op_endpoint) {

  // Remove Old Associations:
  //TODO
  $openid_association = Database::get_main_table(TABLE_MAIN_OPENID_ASSOCIATION);
  api_sql_query("DELETE FROM $openid_association WHERE created + expires_in < %d", time());

  // Check to see if we have an association for this IdP already
  $assoc_handle = api_sql_query("SELECT assoc_handle FROM $openid_association WHERE idp_endpoint_uri = '%s'", $op_endpoint);
  if (Database::num_rows($assoc_handle)<=1) {
    $mod = OPENID_DH_DEFAULT_MOD;
    $gen = OPENID_DH_DEFAULT_GEN;
    $r = _openid_dh_rand($mod);
    $private = bcadd($r, 1);
    $public = bcpowmod($gen, $private, $mod);

    // If there is no existing association, then request one
    $assoc_request = openid_association_request($public);
    $assoc_message = _openid_encode_message(_openid_create_message($assoc_request));
    $assoc_headers = array('Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8');
    //TODO
    $assoc_result = openid_http_request($op_endpoint, $assoc_headers, 'POST', $assoc_message);
    if (isset($assoc_result->error)) {
      return FALSE;
    }

    $assoc_response = _openid_parse_message($assoc_result->data);
    if (isset($assoc_response['mode']) && $assoc_response['mode'] == 'error') {
        return FALSE;
    }

    if ($assoc_response['session_type'] == 'DH-SHA1') {
      $spub = _openid_dh_base64_to_long($assoc_response['dh_server_public']);
      $enc_mac_key = base64_decode($assoc_response['enc_mac_key']);
      $shared = bcpowmod($spub, $private, $mod);
      $assoc_response['mac_key'] = base64_encode(_openid_dh_xorsecret($shared, $enc_mac_key));
    }
    //TODO
   	$openid_association = Database::get_main_table(TABLE_MAIN_OPENID_ASSOCIATION);
    api_sql_query(sprintf("INSERT INTO $openid_association (idp_endpoint_uri, session_type, assoc_handle, assoc_type, expires_in, mac_key, created) VALUES('%s', '%s', '%s', '%s', %d, '%s', %d)",
             $op_endpoint, $assoc_response['session_type'], $assoc_response['assoc_handle'], $assoc_response['assoc_type'], $assoc_response['expires_in'], $assoc_response['mac_key'], time()));

    $assoc_handle = $assoc_response['assoc_handle'];
  }

  return $assoc_handle;
}

/**
 * ?
 */
function openid_association_request($public) {
  require_once(api_get_path(SYS_CODE_PATH).'auth/openid/openid.conf.php');

  $request = array(
    'openid.ns' => OPENID_NS_2_0,
    'openid.mode' => 'associate',
    'openid.session_type' => 'DH-SHA1',
    'openid.assoc_type' => 'HMAC-SHA1'
  );

  if ($request['openid.session_type'] == 'DH-SHA1' || $request['openid.session_type'] == 'DH-SHA256') {
    $cpub = _openid_dh_long_to_base64($public);
    $request['openid.dh_consumer_public'] = $cpub;
  }

  return $request;
}

/**
 * 
 */
function openid_authentication_request($claimed_id, $identity, $return_to = '', $assoc_handle = '', $version = 2) {

  $realm = ($return_to) ? $return_to : api_get_self();

  $ns = ($version == 2) ? OPENID_NS_2_0 : OPENID_NS_1_0;
  $request =  array(
    'openid.ns' => $ns,
    'openid.mode' => 'checkid_setup',
    'openid.identity' => $identity,
    'openid.claimed_id' => $claimed_id,
    'openid.assoc_handle' => $assoc_handle,
    'openid.return_to' => $return_to,
  );

  if ($version == 2) {
    $request['openid.realm'] = $realm;
  }
  else {
    $request['openid.trust_root'] = $realm;
  }

  // Simple Registration - we don't ask lastname and firstname because the only
  // available similar data is "fullname" and we would have to guess where to split 
  $request['openid.sreg.required'] = 'nickname,email';
  $request['openid.ns.sreg'] = "http://openid.net/extensions/sreg/1.1";

  //$request = array_merge($request, module_invoke_all('openid', 'request', $request));
  //$request = array_merge($request);

  return $request;
}

/**
 * Attempt to verify the response received from the OpenID Provider.
 *
 * @param $op_endpoint The OpenID Provider URL.
 * @param $response Array of repsonse values from the provider.
 *
 * @return boolean
 */
function openid_verify_assertion($op_endpoint, $response) {

  $valid = FALSE;

	//TODO
  $openid_association = Database::get_main_table(TABLE_MAIN_OPENID_ASSOCIATION);
  $sql = sprintf("SELECT * FROM $openid_association WHERE assoc_handle = '%s'", $response['openid.assoc_handle']);
  $res = api_sql_query($sql);
  $association = Database::fetch_object($res);
  if ($association && isset($association->session_type)) {
    $keys_to_sign = explode(',', $response['openid.signed']);
    $self_sig = _openid_signature($association, $response, $keys_to_sign);
    if ($self_sig == $response['openid.sig']) {
      $valid = TRUE;
    }
    else {
      $valid = FALSE;
    }
  }
  else {
    $request = $response;
    $request['openid.mode'] = 'check_authentication';
    $message = _openid_create_message($request);
    $headers = array('Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8');
    $result = openid_http_request($op_endpoint, $headers, 'POST', _openid_encode_message($message));
    if (!isset($result->error)) {
      $response = _openid_parse_message($result->data);
      if (strtolower(trim($response['is_valid'])) == 'true') {
        $valid = TRUE;
      }
      else {
        $valid = FALSE;
      }
    }
  }

  return $valid;
}

/**
 * Make a HTTP request - This function has been copied straight over from Drupal 6 code (drupal_http_request)
 */
function openid_http_request($url, $headers = array(), $method = 'GET', $data = NULL, $retry = 3) {
  $result = new stdClass();

  // Parse the URL and make sure we can handle the schema.
  $uri = parse_url($url);

  switch ($uri['scheme']) {
    case 'http':
      $port = isset($uri['port']) ? $uri['port'] : 80;
      $host = $uri['host'] . ($port != 80 ? ':'. $port : '');
      $fp = @fsockopen($uri['host'], $port, $errno, $errstr, 15);
      break;
    case 'https':
      // Note: Only works for PHP 4.3 compiled with OpenSSL.
      $port = isset($uri['port']) ? $uri['port'] : 443;
      $host = $uri['host'] . ($port != 443 ? ':'. $port : '');
      $fp = @fsockopen('ssl://'. $uri['host'], $port, $errno, $errstr, 20);
      break;
    default:
      $result->error = 'invalid schema '. $uri['scheme'];
      return $result;
  }

  // Make sure the socket opened properly.
  if (!$fp) {
    // When a network error occurs, we make sure that it is a negative number so
    // it can clash with the HTTP status codes.
    $result->code = -$errno;
    $result->error = trim($errstr);
    return $result;
  }

  // Construct the path to act on.
  $path = isset($uri['path']) ? $uri['path'] : '/';
  if (isset($uri['query'])) {
    $path .= '?'. $uri['query'];
  }

  // Create HTTP request.
  $defaults = array(
    // RFC 2616: "non-standard ports MUST, default ports MAY be included".
    // We don't add the port to prevent from breaking rewrite rules checking the
    // host that do not take into account the port number.
    'Host' => "Host: $host",
    'User-Agent' => 'User-Agent: Dokeos (+http://dokeos.com/)',
    'Content-Length' => 'Content-Length: '. strlen($data)
  );

  // If the server url has a user then attempt to use basic authentication
  if (isset($uri['user'])) {
    $defaults['Authorization'] = 'Authorization: Basic '. base64_encode($uri['user'] . (!empty($uri['pass']) ? ":". $uri['pass'] : ''));
  }

  foreach ($headers as $header => $value) {
    $defaults[$header] = $header .': '. $value;
  }

  $request = $method .' '. $path ." HTTP/1.0\r\n";
  $request .= implode("\r\n", $defaults);
  $request .= "\r\n\r\n";
  if ($data) {
    $request .= $data ."\r\n";
  }
  $result->request = $request;

  fwrite($fp, $request);

  // Fetch response.
  $response = '';
  while (!feof($fp) && $chunk = fread($fp, 1024)) {
    $response .= $chunk;
  }
  fclose($fp);

  // Parse response.
  list($split, $result->data) = explode("\r\n\r\n", $response, 2);
  $split = preg_split("/\r\n|\n|\r/", $split);

  list($protocol, $code, $text) = explode(' ', trim(array_shift($split)), 3);
  $result->headers = array();

  // Parse headers.
  while ($line = trim(array_shift($split))) {
    list($header, $value) = explode(':', $line, 2);
    if (isset($result->headers[$header]) && $header == 'Set-Cookie') {
      // RFC 2109: the Set-Cookie response header comprises the token Set-
      // Cookie:, followed by a comma-separated list of one or more cookies.
      $result->headers[$header] .= ','. trim($value);
    }
    else {
      $result->headers[$header] = trim($value);
    }
  }

  $responses = array(
    100 => 'Continue', 101 => 'Switching Protocols',
    200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content',
    300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect',
    400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Time-out', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Large', 415 => 'Unsupported Media Type', 416 => 'Requested range not satisfiable', 417 => 'Expectation Failed',
    500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Time-out', 505 => 'HTTP Version not supported'
  );
  // RFC 2616 states that all unknown HTTP codes must be treated the same as the
  // base code in their class.
  if (!isset($responses[$code])) {
    $code = floor($code / 100) * 100;
  }

  switch ($code) {
    case 200: // OK
    case 304: // Not modified
      break;
    case 301: // Moved permanently
    case 302: // Moved temporarily
    case 307: // Moved temporarily
      $location = $result->headers['Location'];

      if ($retry) {
        $result = openid_http_request($result->headers['Location'], $headers, $method, $data, --$retry);
        $result->redirect_code = $result->code;
      }
      $result->redirect_url = $location;

      break;
    default:
      $result->error = $text;
  }

  $result->code = $code;
  return $result;
}