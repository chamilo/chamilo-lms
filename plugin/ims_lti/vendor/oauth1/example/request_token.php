<?php
require_once("common.inc.php");

try {
  $req = OAuthRequest::from_request();
  $token = $test_server->fetch_request_token($req);
  print $token;
} catch (OAuthException $e) {
  print($e->getMessage() . "\n<hr />\n");
  print_r($req);
  die();
}

?>
