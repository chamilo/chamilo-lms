= Chamilo Webservices =

Chamilo webservices are not the greatest API you can find around, but they 
kind of work, as long as you don't get fooled by the many files in this
folder.

The main maintained script is registration.soap.php
The way to call it is relatively well described in the example:
 client_soap.php

Basically, we have a weird way of authenticating you (until we release APIv2
with OAuth or similar authentication methods). We ask you to include the
public IP of the server calling the webservice inside the key, and to combine
that key with the $_configuration['security_key'] value in 
app/config/configuration.php.

You can get your own public IP by doing a wget on the testip.php file in this
folder (you can do that automatically through a file_get_contents() or fopen()
as well, if you need to).
There is a way to alter this mechanism by adding a specific IP to the file
webservice-auth-ip.conf.php.

Once you're all setup with the key to connect to Chamilo, just call your 
webservices like you would normally do through SOAP (that's where the 
client_soap.php file can really get you through).

Most of the other files are (failed) attempts at redesigning the API. We hope
we'll get the chance to provide a better API soon.