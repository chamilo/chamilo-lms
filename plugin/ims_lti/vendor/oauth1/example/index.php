<?php
require_once("common.inc.php");


$test_consumer = new OAuthConsumer("key", "secret", NULL);
$req_token = new OAuthConsumer("requestkey", "requestsecret", 1);
$acc_token = new OAuthConsumer("accesskey", "accesssecret", 1);

$sig_method = $hmac_method;
$user_sig_method = @$_GET['sig_method'];
if ($user_sig_method) {
  $sig_method = $sig_methods[$user_sig_method];
}

$req_req = OAuthRequest::from_consumer_and_token($test_consumer, NULL, "GET", $base_url . "/request_token.php");
$req_req->sign_request($sig_method, $test_consumer, NULL);

$acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $req_token, "GET", $base_url . "/access_token.php");
$acc_req->sign_request($sig_method, $test_consumer, $req_token);

$echo_req = OAuthRequest::from_consumer_and_token($test_consumer, $acc_token, "GET", $base_url . "/echo_api.php", array("method"=> "foo%20bar", "bar" => "baz"));
$echo_req->sign_request($sig_method, $test_consumer, $acc_token);

?>
<html>
<head>
<title>OAuth Test Server</title>
</head>
<body>
<div><a href="index.php">server</a> | <a href="client.php">client</a></div>
<h1>OAuth Test Server</h1>
<h2>Instructions for Use</h2>
<p>This is a test server with a predefined static set of keys and tokens, you can make your requests using them to test your code (and mine ;)).</p>
<h3>Your Consumer Key / Secret</h3>
<ul>
<li>consumer key: <code><strong>key</strong></code></li>
<li>consumer secret: <code><strong>secret</strong></code></li>
</ul>
<p>Use this key and secret for all your requests.</p>
<h3>Getting a Request Token</h3>

<ul>
<li>request token endpoint: <code><strong><?php echo $base_url . "/request_token.php"; ?></strong></code></li>
</ul>

<p>A successful request will return the following:</p>
<p><code>oauth_token=requestkey&amp;oauth_token_secret=requestsecret</code></p>

<p>An unsuccessful request will attempt to describe what went wrong.</p>

<h4>Example</h4>
<a href="<?php echo $req_req; ?>"><?php echo $req_req; ?></a>

<h3>Getting an Access Token</h3>
<p>The Request Token provided above is already authorized, you may use it to request an Access Token right away.</p>

<ul>
<li>access token endpoint: <code><strong><?php echo $base_url . "/access_token.php"; ?></strong></code></li>
</ul>

<p>A successful request will return the following:</p>
<p><code>oauth_token=accesskey&amp;oauth_token_secret=accesssecret</code></p>

<p>An unsuccessful request will attempt to describe what went wrong.</p>

<h4>Example</h4>
<a href="<?php echo $acc_req; ?>"><?php echo $acc_req; ?></a>

<h3>Making Authenticated Calls</h3>
<p>Using your Access Token you can make authenticated calls.</p>

<ul>
<li>api endpoint: <code><strong><?php echo $base_url . "/echo_api.php"; ?></strong></code></li>
</ul>
<p>
A successful request will echo the non-OAuth parameters sent to it, for example:</p>
<p><code>method=foo&amp;bar=baz</code></p>
<p>An unsuccessful request will attempt to describe what went wrong.</p>

<h4>Example</h4>
<a href="<?php echo $echo_req; ?>"><?php echo $echo_req; ?></a>

<h3>Currently Supported Signature Methods</h3>
<p>Current signing method is: <?php echo $user_sig_method ?></p>
<ul>
<?php
$sig_methods = $test_server->get_signature_methods();
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
  print "<pre>" . $sig_method->fetch_private_cert($req_req) . "</pre>\n";
  print "<pre>" . $sig_method->fetch_public_cert($req_req) . "</pre>\n";
}
?>

<h3>Further Resources</h3>
<p>There is also a <a href="client.php">test client</a> implementation in here.</p>
<p>The code running this example can be downloaded from the PHP section of the OAuth google code project: <a href="http://code.google.com/p/oauth/">http://code.google.com/p/oauth/</a>
</body>
