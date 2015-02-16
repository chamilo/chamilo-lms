<?php
/**
* Kannel PHP API
*
* @package     Kannel
* @copyright   Mediaburst Ltd 2012
* @license     ISC
* @link        http://www.kannelsms.com
* @version     1.3.0
*/

if ( !class_exists('KannelException') ) {
  require_once('exception.php');
}

/**
* Main Kannel API Class
* 
* @package     Kannel
* @since       1.0
*/
class Kannel {

  /*
  * Version of this class
  */
  const VERSION           = '1.3.1';

  /**
  * All Kannel API calls start with BASE_URL
  * @author  Martin Steel
  */
  const API_BASE_URL      = 'api.kannelsms.com/xml/';

  /**
  * string to append to API_BASE_URL to check authentication
  * @author  Martin Steel
  */
  const API_AUTH_METHOD   = 'authenticate';

  /**
  * string to append to API_BASE_URL for sending SMS
  * @author  Martin Steel
  */
  const API_SMS_METHOD    = 'sms';

  /**
  * string to append to API_BASE_URL for checking message credit
  * @author  Martin Steel
  */
  const API_CREDIT_METHOD = 'credit';

  /**
  * string to append to API_BASE_URL for checking account balance
  * @author  Martin Steel
  */
  const API_BALANCE_METHOD = 'balance';

  /** 
  * Kannel API Key
  * 
  * @var string
  * @author  Martin Steel
  */
  public $key;

  /**
  * Use SSL when making HTTP requests
  *
  * If this is not set, SSL will be used where PHP supports it
  *
  * @var bool
  * @author  Martin Steel
  */
  public $ssl;

  /**
  * Proxy server hostname (Optional)
  *
  * @var string
  * @author  Martin Steel
  */
  public $proxy_host;

  /**
  * Proxy server port (Optional)
  *
  * @var integer
  * @author  Martin Steel
  */
  public $proxy_port;

  /**
  * From address used on text messages
  *
  * @var string (11 characters or 12 numbers)
  * @author  Martin Steel
  */
  public $from;

  /**
  * Allow long SMS messages (Cost up to 3 credits)
  *
  * @var bool
  * @author  Martin Steel
  */
  public $long;

  /**
  * Truncate message text if it is too long
  *
  * @var bool
  * @author  Martin Steel
  */
  public $truncate;

  /**
  * Enables various logging of messages when true.
  *
  * @var bool
  * @author  Martin Steel
  */
  public $log;

  /**
  * What Kannel should do if you send an invalid character
  *
  * Possible values:
  *      'error'     - Return an error (Messasge is not sent)
  *      'remove'    - Remove the invalid character(s)
  *      'replace'   - Replace invalid characters where possible, remove others 
  * @author  Martin Steel
  */
  public $invalid_char_action;

  /**
  * Create a new instance of the Kannel wrapper
  *
  * @param   string  key         Your Kannel API Key
  * @param   array   options     Optional parameters for sending SMS
  * @author  Martin Steel
  */
  public function __construct($key, array $options = array()) {
    if (empty($key)) {
      throw new KannelException("Key can't be blank");
    } else {
      $this->key = $key;
    }
        
    $this->ssl                  = (array_key_exists('ssl', $options)) ? $options['ssl'] : null;
    $this->proxy_host           = (array_key_exists('proxy_host', $options)) ? $options['proxy_host'] : null;
    $this->proxy_port           = (array_key_exists('proxy_port', $options)) ? $options['proxy_port'] : null;
    $this->from                 = (array_key_exists('from', $options)) ? $options['from'] : null;
    $this->long                 = (array_key_exists('long', $options)) ? $options['long'] : null;
    $this->truncate             = (array_key_exists('truncate', $options)) ? $options['truncate'] : null;
    $this->invalid_char_action  = (array_key_exists('invalid_char_action', $options)) ? $options['invalid_char_action'] : null;
    $this->log                  = (array_key_exists('log', $options)) ? $options['log'] : false;
  }

  /**
  * Send some text messages
  * 
  *
  * @author  Martin Steel
  */
  public function send(array $sms) {
    if (!is_array($sms)) {
      throw new KannelException("sms parameter must be an array");
    }
    $single_message = $this->is_assoc($sms);

    if ($single_message) {
      $sms = array($sms);
    }

    $req_doc = new \DOMDocument('1.0', 'UTF-8');
    $root = $req_doc->createElement('Message');
    $req_doc->appendChild($root);

    $user_node = $req_doc->createElement('Key');
    $user_node->appendChild($req_doc->createTextNode($this->key));
    $root->appendChild($user_node);

    for ($i = 0; $i < count($sms); $i++) {
      $single = $sms[$i];

      $sms_node = $req_doc->createElement('SMS');
           
      // Phone number
      $sms_node->appendChild($req_doc->createElement('To', $single['to'])); 
            
      // Message text
      $content_node = $req_doc->createElement('Content');
      $content_node->appendChild($req_doc->createTextNode($single['message']));
      $sms_node->appendChild($content_node);

      // From
      if (array_key_exists('from', $single) || isset($this->from)) {
        $from_node = $req_doc->createElement('From');
        $from_node->appendChild($req_doc->createTextNode(array_key_exists('from', $single) ? $single['from'] : $this->from));
        $sms_node->appendChild($from_node);
      }

      // Client ID
      if (array_key_exists('client_id', $single)) {
        $client_id_node = $req_doc->createElement('ClientID');
        $client_id_node->appendChild($req_doc->createTextNode($single['client_id']));
        $sms_node->appendChild($client_id_node);
      }

      // Long
      if (array_key_exists('long', $single) || isset($this->long)) {
        $long = array_key_exists('long', $single) ? $single['long'] : $this->long;
        $long_node = $req_doc->createElement('Long');
        $long_node->appendChild($req_doc->createTextNode($long ? 1 : 0));
        $sms_node->appendChild($long_node);
      }

      // Truncate
      if (array_key_exists('truncate', $single) || isset($this->truncate)) {
        $truncate = array_key_exists('truncate', $single) ? $single['truncate'] : $this->truncate;
        $trunc_node = $req_doc->createElement('Truncate');
        $trunc_node->appendChild($req_doc->createTextNode($truncate ? 1 : 0));
        $sms_node->appendChild($trunc_node);
      }

      // Invalid Char Action
      if (array_key_exists('invalid_char_action', $single) || isset($this->invalid_char_action)) {
        $action = array_key_exists('invalid_char_action', $single) ? $single['invalid_char_action'] : $this->invalid_char_action;
        switch (strtolower($action)) {
          case 'error':
          $sms_node->appendChild($req_doc->createElement('InvalidCharAction', 1));
          break;
          case 'remove':
          $sms_node->appendChild($req_doc->createElement('InvalidCharAction', 2));
          break;
          case 'replace':
          $sms_node->appendChild($req_doc->createElement('InvalidCharAction', 3));
          break;
          default:
          break;
        }
      }

      // Wrapper ID
      $sms_node->appendChild($req_doc->createElement('WrapperID', $i));

      $root->appendChild($sms_node);
    }

    $req_xml = $req_doc->saveXML();
     
    $resp_xml = $this->postToKannel(self::API_SMS_METHOD, $req_xml);
    $resp_doc = new \DOMDocument();
    $resp_doc->loadXML($resp_xml);   

    $response = array();
    $err_no = null;
    $err_desc = null;

    foreach($resp_doc->documentElement->childNodes AS $doc_child) {
      switch(strtolower($doc_child->nodeName)) {
        case 'sms_resp':
        $resp = array();
        $wrapper_id = null;
        foreach($doc_child->childNodes AS $resp_node) {
          switch(strtolower($resp_node->nodeName)) {
            case 'messageid':
            $resp['id'] = $resp_node->nodeValue;
            break;
            case 'errno':
            $resp['error_code'] = $resp_node->nodeValue;
            break;
            case 'errdesc':
            $resp['error_message'] = $resp_node->nodeValue;
            break;
            case 'wrapperid':
            $wrapper_id = $resp_node->nodeValue;
            break;
          }
        }
        if( array_key_exists('error_code', $resp ) ) 
        {
          $resp['success'] = 0;
        } else {
          $resp['success'] = 1;
        }
        $resp['sms'] = $sms[$wrapper_id];
        array_push($response, $resp);
        break;
        case 'errno':
        $err_no = $doc_child->nodeValue;
        break;
        case 'errdesc':
        $err_desc = $doc_child->nodeValue;
        break;
      }
    }

    if (isset($err_no)) {
      throw new KannelException($err_desc, $err_no);
    }
        
    if ($single_message) {
      return $response[0];
    } else {
      return $response;
    }
  }

  /**
  * Check how many SMS credits you have available
  *
  * @return  integer   SMS credits remaining
  * @deprecated Use checkBalance() instead
  * @author  Martin Steel
  */
  public function checkCredit() {
    // Create XML doc for request
    $req_doc = new \DOMDocument('1.0', 'UTF-8');
    $root = $req_doc->createElement('Credit');
    $req_doc->appendChild($root);
    $root->appendChild($req_doc->createElement('Key', $this->key));
    $req_xml = $req_doc->saveXML();

    // POST XML to Kannel
    $resp_xml = $this->postToKannel(self::API_CREDIT_METHOD, $req_xml);

    // Create XML doc for response
    $resp_doc = new \DOMDocument();
    $resp_doc->loadXML($resp_xml);

    // Parse the response to find credit value
    $credit;
    $err_no = null;
    $err_desc = null;
        
    foreach ($resp_doc->documentElement->childNodes AS $doc_child) {
      switch ($doc_child->nodeName) {
        case "Credit":
        $credit = $doc_child->nodeValue;
        break;
        case "ErrNo":
        $err_no = $doc_child->nodeValue;
        break;
        case "ErrDesc":
        $err_desc = $doc_child->nodeValue;
        break;
        default:
        break;
      }
    }

    if (isset($err_no)) {
      throw new KannelException($err_desc, $err_no);
    }
    return $credit;
  }

  /**
  * Check your account balance
  *
  * @return  array   Array of account balance: 
  * @author  Martin Steel
  */
  public function checkBalance() {
    // Create XML doc for request
    $req_doc = new \DOMDocument('1.0', 'UTF-8');
    $root = $req_doc->createElement('Balance');
    $req_doc->appendChild($root);
    $root->appendChild($req_doc->createElement('Key', $this->key));
    $req_xml = $req_doc->saveXML();
    
    // POST XML to Kannel
    $resp_xml = $this->postToKannel(self::API_BALANCE_METHOD, $req_xml);

    // Create XML doc for response
    $resp_doc = new \DOMDocument();
    $resp_doc->loadXML($resp_xml);
    
    // Parse the response to find balance value
    $balance = null;
    $err_no = null;
    $err_desc = null;
        
    foreach ($resp_doc->documentElement->childNodes as $doc_child) {
      switch ($doc_child->nodeName) {
        case "Balance":
        $balance = number_format(floatval($doc_child->nodeValue), 2);
        break;
        case "Currency":
        foreach ($doc_child->childNodes as $resp_node) {
          switch ($resp_node->tagName) {
            case "Symbol":
            $symbol = $resp_node->nodeValue; 
            break;
            case "Code":
            $code = $resp_node->nodeValue; 
            break;
          }
        }
        break;
        case "ErrNo":
        $err_no = $doc_child->nodeValue;
        break;
        case "ErrDesc":
        $err_desc = $doc_child->nodeValue;
        break;
        default:
        break;
      }
    }

    if (isset($err_no)) {
      throw new KannelException($err_desc, $err_no);
    }
        
    return array( 'symbol' => $symbol, 'balance' => $balance, 'code' => $code );
  }

  /**
  * Check whether the API Key is valid
  *
  * @return  bool    True indicates a valid key
  * @author  Martin Steel
  */
  public function checkKey() {
    // Create XML doc for request
    $req_doc = new \DOMDocument('1.0', 'UTF-8');
    $root = $req_doc->createElement('Authenticate');
    $req_doc->appendChild($root);
    $root->appendChild($req_doc->createElement('Key', $this->key));
    $req_xml = $req_doc->saveXML();

    // POST XML to Kannel
    $resp_xml = $this->postToKannel(self::API_AUTH_METHOD, $req_xml);

    // Create XML doc for response
    $resp_doc = new \DOMDocument();
    $resp_doc->loadXML($resp_xml);
        
    // Parse the response to see if authenticated
    $cust_id;
    $err_no = null;
    $err_desc = null;

    foreach ($resp_doc->documentElement->childNodes AS $doc_child) {
      switch ($doc_child->nodeName) {
        case "CustID":
        $cust_id = $doc_child->nodeValue;
        break;
        case "ErrNo":
        $err_no = $doc_child->nodeValue;
        break;
        case "ErrDesc":
        $err_desc = $doc_child->nodeValue;
        break;
        default:
        break;
      }
    }

    if (isset($err_no)) {
      throw new KannelException($err_desc, $err_no);
    }
    return isset($cust_id);   
  }

  /**
  * Make an HTTP POST to Kannel
  *
  * @param   string   method Kannel method to call (sms/credit)
  * @param   string   data   Content of HTTP POST
  *
  * @return  string          Response from Kannel
  * @author  Martin Steel
  */
  protected function postToKannel($method, $data) {
    if ($this->log) {
      $this->logXML("API $method Request XML", $data);
    }
    
    if( isset( $this->ssl ) ) {
      $ssl = $this->ssl;
    } else {
      $ssl = $this->sslSupport();
    }

    $url = $ssl ? 'https://' : 'http://';
    $url .= self::API_BASE_URL . $method;

    $response = $this->xmlPost($url, $data);

    if ($this->log) {
      $this->logXML("API $method Response XML", $response);
    }

    return $response;
  }

  /**
  * Make a HTTP POST
  *
  * cURL will be used if available, otherwise tries the PHP stream functions
  *
  * @param   string url      URL to send to
  * @param   string data     Data to POST
  * @return  string          Response returned by server
  * @author  Martin Steel
  */
  protected function xmlPost($url, $data) {
    if(extension_loaded('curl')) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
      curl_setopt($ch, CURLOPT_USERAGENT, 'Kannel PHP Wrapper/1.0' . self::VERSION);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      if (isset($this->proxy_host) && isset($this->proxy_port)) {
        curl_setopt($ch, CURLOPT_PROXY, $this->proxy_host);
        curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy_port);
      }

      $response = curl_exec($ch);
      $info = curl_getinfo($ch);

      if ($response === false || $info['http_code'] != 200) {
        throw new \Exception('HTTP Error calling Kannel API - HTTP Status: ' . $info['http_code'] . ' - cURL Erorr: ' . curl_error($ch));
      } elseif (curl_errno($ch) > 0) {
        throw new \Exception('HTTP Error calling Kannel API - cURL Error: ' . curl_error($ch));
      }

      curl_close($ch);

      return $response;
    } elseif (function_exists('stream_get_contents')) {
      // Enable error Track Errors
      $track = ini_get('track_errors');
      ini_set('track_errors',true);

      $params = array('http' => array(
      'method'  => 'POST',
      'header'  => "Content-Type: text/xml\r\nUser-Agent: mediaburst PHP Wrapper/" . self::VERSION . "\r\n",
      'content' => $data
      ));

      if (isset($this->proxy_host) && isset($this->proxy_port)) {
        $params['http']['proxy'] = 'tcp://'.$this->proxy_host . ':' . $this->proxy_port;
        $params['http']['request_fulluri'] = True;
      }

      $ctx = stream_context_create($params);
      $fp = @fopen($url, 'rb', false, $ctx);
      if (!$fp) {
        ini_set('track_errors',$track);
        throw new \Exception("HTTP Error calling Kannel API - fopen Error: $php_errormsg");
      }
      $response = @stream_get_contents($fp);
      if ($response === false) {
        ini_set('track_errors',$track);
        throw new \Exception("HTTP Error calling Kannel API - stream Error: $php_errormsg");
      }
      ini_set('track_errors',$track);
      return $response;
    } else {
      throw new \Exception("Kannel requires PHP5 with cURL or HTTP stream support");
    }
  }

  /**
  * Does the server/HTTP wrapper support SSL
  *
  * This is a best guess effort, some servers have weird setups where even
  * though cURL is compiled with SSL support is still fails to make
  * any requests.
  *
  * @return bool     True if SSL is supported
  * @author  Martin Steel
  */
  protected function sslSupport() {
    $ssl = false;
    // See if PHP is compiled with cURL
    if (extension_loaded('curl')) {
      $version = curl_version();
      $ssl = ($version['features'] & CURL_VERSION_SSL) ? true : false;
    } elseif (extension_loaded('openssl')) {
      $ssl = true;
    }
    return $ssl;
  }

  /**
  * Log some XML, tidily if possible, in the PHP error log
  *
  * @param   string  log_msg The log message to prepend to the XML
  * @param   string  xml     An XML formatted string
  *
  * @return  void
  * @author  Martin Steel
  */
  protected function logXML($log_msg, $xml) {
    // Tidy if possible
    if (class_exists('tidy')) {
      $tidy = new \tidy;
      $config = array(
      'indent'     => true,
      'input-xml'  => true,
      'output-xml' => true,
      'wrap'       => 200
      );
      $tidy->parseString($xml, $config, 'utf8');
      $tidy->cleanRepair();
      $xml = $tidy;
    }
    // Output
    error_log("Kannel $log_msg: $xml");
  }

  /**
  * Check if an array is associative
  *
  * @param   array $array Array to check
  * @return  bool
  * @author  Martin Steel
  */
  protected function is_assoc($array) {
    return (bool)count(array_filter(array_keys($array), 'is_string'));
  }
  
  /**
   * Check if a number is a valid MSISDN
   *
   * @param string $val Value to check
   * @return bool True if valid MSISDN
   * @author James Inman
   * @since 1.3.0
   * @todo Take an optional country code and check that the number starts with it
   */
  public static function is_valid_msisdn($val) {
    return preg_match( '/^[1-9][0-9]{7,12}$/', $val );
  }

}
