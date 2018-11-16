<?php
require_once("common.inc.php");

$key = @$_REQUEST['key'];
$secret = @$_REQUEST['secret'];
$token = @$_REQUEST['token'];
$token_secret = @$_REQUEST['token_secret'];
$endpoint = @$_REQUEST['endpoint'];
$action = @$_REQUEST['action'];
$dump_request = @$_REQUEST['dump_request'];
$user_sig_method = @$_REQUEST['sig_method'];
$sig_method = $hmac_method;
if ($user_sig_method) {
  $sig_method = $sig_methods[$user_sig_method];
}

$test_consumer = new OAuthConsumer($key, $secret, NULL);

$test_token = NULL;
if ($token) {
  $test_token = new OAuthConsumer($token, $token_secret);
}


if ($action == "request_token") {
  $parsed = parse_url($endpoint);
  $params = array();
  parse_str($parsed['query'], $params);

  $req_req = OAuthRequest::from_consumer_and_token($test_consumer, NULL, "GET", $endpoint, $params);
  $req_req->sign_request($sig_method, $test_consumer, NULL);
  if ($dump_request) {
    Header('Content-type: text/plain');
    print "request url: " . $req_req->to_url(). "\n";
    print_r($req_req);
    exit;
  }
  Header("Location: $req_req");
} 
else if ($action == "authorize") {
  $callback_url = "$base_url/client.php?key=$key&secret=$secret&token=$token&token_secret=$token_secret&endpoint=" . urlencode($endpoint);
  $auth_url = $endpoint . "?oauth_token=$token&oauth_callback=".urlencode($callback_url);
  if ($dump_request) {
    Header('Content-type: text/plain');
    print("auth_url: " . $auth_url);
    exit;
  }
  Header("Location: $auth_url");
}
else if ($action == "access_token") {
  $parsed = parse_url($endpoint);
  $params = array();
  parse_str($parsed['query'], $params);

  $acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $test_token, "GET", $endpoint, $params);
  $acc_req->sign_request($sig_method, $test_consumer, $test_token);
  if ($dump_request) {
    Header('Content-type: text/plain');
    print "request url: " . $acc_req->to_url() . "\n";
    print_r($acc_req);
    exit;
  }
  Header("Location: $acc_req");
}

?>
<html>
<head>
<title>OAuth Test Client</title>
</head>
<body>
<div><a href="index.php">server</a> | <a href="client.php">client</a></div>
<h1>OAuth Test Client</h1>
<h2>Instructions for Use</h2>
<p>This is a test client that will let you test your OAuth server code. Enter the appropriate information below to test.</p>
<p>Note: we don't store any of the information you type in.</p>

<form method="POST" name="oauth_client">
<h3>Choose a Signature Method</h3>
<select name="sig_method">
<?php
foreach ($sig_methods as $name=> $method) {
  $selected = "";
  if ($name == $sig_method->get_name()) {
    $selected = " selected='selected'";
  }
  print "<option value='$name'$selected>$name</option>\n";
}
?>
</select>
<h3>Enter The Endpoint to Test</h3>
endpoint: <input type="text" name="endpoint" value="<?php echo $endpoint; ?>" size="100"/><br />
<small style="color: green">Note: You can include query parameters in there to have them parsed in and signed too</small>
<h3>Enter Your Consumer Key / Secret</h3>
consumer key: <input type="text" name="key" value="<?php echo $key; ?>" /><br />
consumer secret: <input type="text" name="secret" value="<?php echo $secret;?>" /><br />
dump request, don't redirect: <input type="checkbox" name="dump_request" value="1" <?php if ($dump_request) echo 'checked="checked"'; ?>/><br />
make a token request (don't forget to copy down the values you get)
<input type="submit" name="action" value="request_token" />
<h3>Enter Your Request Token / Secret</h3>
token: <input type="text" name="token" value="<?php echo $token; ?>" /><br />
token secret: <input type="text" name="token_secret" value="<?php echo $token_secret; ?>" /><br />
<p><strong>Don't forget to update your endpoint to point at the auth or access token url</strong></p>
try to authorize this token: <input type="submit" name="action" value="authorize" /><br />
try to get an access token: <input type="submit" name="action" value="access_token" /><br />

<h3>Currently Supported Signature Methods</h3>
<p>Current signing method is: <?php echo $sig_method->get_name() ?></p>
<ul>
<?php
foreach ($sig_methods as $key => $method) {
  
  print "<li>$key";
  if ($key != $sig_method->get_name()) {
    print "(<a href='?sig_method=$key'>switch</a>)";
  }
  print "</li>\n";
}
?>
</ul>

<?php 
if ("RSA-SHA1" == $sig_method->get_name()) {
  // passing test_server as a dummy referecne
  print "<pre>" . $sig_method->fetch_private_cert($test_server). "</pre>\n";
  print "<pre>" . $sig_method->fetch_public_cert($test_server) . "</pre>\n";
}
?>

<h3>Further Resources</h3>
<p>There is also a <a href="index.php">test server</a> implementation in here.</p>
<p>The code running this example can be downloaded from the PHP section of the OAuth google code project: <a href="http://code.google.com/p/oauth/">http://code.google.com/p/oauth/</a>
</body>
