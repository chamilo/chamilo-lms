<?php

include_once(__DIR__."/../lib/Google/Authenticator/FixedBitNotation.php");
include_once(__DIR__."/../lib/Google/Authenticator/GoogleAuthenticator.php");

$secret = 'XVQ2UIGO75XRUKJO';
$time = floor(time() / 30);
$code = "846474";

$g = new \Google\Authenticator\GoogleAuthenticator();

print "Current Code is: ";
print $g->getCode($secret);

print "\n";

print "Check if $code is valid: ";

if ($g->checkCode($secret, $code)) {
    print "YES \n";
} else {
    print "NO \n";
}

$secret = $g->generateSecret();
print "Get a new Secret: $secret \n";
print "The QR Code for this secret (to scan with the Google Authenticator App: \n";

print $g->getURL('chregu', 'example.org', $secret);
print "\n";
