<?php
/**
 * Created on 08.11.2006
 * This script gives information to the videoconference scripts (in OpenLaszlo)
 * to use the right URL and ports for the videoconference.
 */
include("../../main/inc/global.inc.php");
api_block_anonymous_users();

printf ('<?xml version="1.0" encoding="UTF-8" ?>');

printf('<dokeosobject>');

printf('<courseobject>');
foreach ($_SESSION['_course'] as $key => $val)	printf('<%s>%s</%s>',$key,utf8_encode($val),$key);
printf('</courseobject>');

printf('<userobject>');
foreach ($_SESSION['_user'] as $key => $val)	printf('<%s>%s</%s>',$key,utf8_encode($val),$key);
printf('</userobject>');

printf('<library>');
printf('<attribute name="rmpthostlocal" value="'.api_get_setting('service_visio','visio_rtmp_host_local').'" type="string" />');
printf('<attribute name="iswebrtmp" value="'.api_get_setting('service_visio','visio_is_web_rtmp').'" type="boolean" />');
printf('<attribute name="rmptport" value="'.api_get_setting('service_visio','visio_rtmp_port').'" type="string" />');
printf('<attribute name="rmptTunnelport" value="'.api_get_setting('service_visio','visio_rtmp_tunnel_port').'" type="string" />');
printf('</library>');

printf('</dokeosobject>');
?>
