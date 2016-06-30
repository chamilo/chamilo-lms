README
<br/><br/>
This plugin adds a button to allow users to log into Chamilo with their Facebook account.<br/><br/>
To display this button on your portal, you have to enable the Facebook authentification and configure it.<br/>
To enable and configure the Facebook authentification on your Chamilo platform, go to Administration > Configuration settings > Facebook<br/>
You should then add the App ID and the Secret Key provided by Facebook inside the app/config/auth.conf.php file<br />
Finally, you will have to set the following line in your app/config/configuration.php<br />
<pre>
$_configuration['facebook_auth'] = 1;
</pre>
This plugin has been developed to be added to the login_top or login_bottom region in Chamilo, but you can put it in whichever region you want.<br/>
