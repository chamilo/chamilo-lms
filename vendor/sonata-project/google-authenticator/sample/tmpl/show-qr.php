<h1>Please scan this </h1>

<p> with <a href="http://www.google.com/support/a/bin/answer.py?hl=en&answer=1037451">the Google Authenticator App</a></p>

<p>
<?php
$link = \Sonata\GoogleAuthenticator\GoogleQrUrl::generate($user->getUsername(), $secret, 'GoogleAuthenticatorExample');
?>

<a  href="<?php echo $link; ?>"><img style="border: 0; padding:10px" src="<?php echo $link; ?>"/></a>
</p>
