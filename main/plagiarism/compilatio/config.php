<?php
/* For licensing terms, see /license.txt */

/*Compilatio's key*/
$compilatioParameter['key'] = api_get_setting('compilatioParamKey');
$compilatioParameter['version'] = '1.2';

/* Url from soap Compilatio server */
/* You can choice the SSL version if you want */
$compilatioParameter['$urlsoap'] = api_get_setting('compilatioParamURLSoap');

/* Parameter form your server's proxy (for call SOAP), don't enter it if you dont have one */

$compilatioParameter['proxy_host'] = '';
$compilatioParameter['proxy_port'] = '';

/* Method to data transmission */
/* Nothing by default, the data transmission is done by SOAP */
/* Wget: the Compilatio's server download the file for the analysis  */
$compilatioParameter['mode_transport'] = '';
/* if the selected data transmission method is wget, you can set up a special url acces */
$compilatioParameter['wget_uri'] = '';

/* If the selected data transmission method is wget, you can specify a login/password acces to the directory */
$compilatioParameter['wget_login'] = '';
$compilatioParameter['wget_password'] = '';

/*Document's maximum size*/
$compilatioParameter['max_filesize'] = 10000000;   // 10Mo

