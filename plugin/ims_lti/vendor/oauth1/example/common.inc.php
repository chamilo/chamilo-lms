<?php
require_once("../init.php");
require_once("../code/OAuth_TestServer.php");

/*
 * Config Section
 */
$domain = $_SERVER['HTTP_HOST'];
$base = "/oauth/example";
$base_url = "http://$domain$base";

/**
 * Some default objects
 */

$test_server = new TestOAuthServer(new MockOAuthDataStore());
$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
$rsa_method = new TestOAuthSignatureMethod_RSA_SHA1();

$test_server->add_signature_method($hmac_method);
$test_server->add_signature_method($plaintext_method);
$test_server->add_signature_method($rsa_method);

$sig_methods = $test_server->get_signature_methods();
?>
