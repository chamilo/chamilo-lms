<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include_once __DIR__.'/../src/FixedBitNotation.php';
include_once __DIR__.'/../src/GoogleAuthenticator.php';
include_once __DIR__.'/../src/GoogleQrUrl.php';

$secret = 'XVQ2UIGO75XRUKJO';
$code = '846474';

$g = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();

echo 'Current Code is: ';
echo $g->getCode($secret);

echo "\n";

echo "Check if $code is valid: ";

if ($g->checkCode($secret, $code)) {
    echo "YES \n";
} else {
    echo "NO \n";
}

$secret = $g->generateSecret();
echo "Get a new Secret: $secret \n";
echo "The QR Code for this secret (to scan with the Google Authenticator App: \n";

echo \Sonata\GoogleAuthenticator\GoogleQrUrl::generate('chregu', $secret, 'GoogleAuthenticatorExample');
echo "\n";
