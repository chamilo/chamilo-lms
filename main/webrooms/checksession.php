<?php
/*
 * Created on 08.11.2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
include("../main/inc/global.inc.php");
api_block_anonymous_users();

printf ('<?xml version="1.0" encoding="UTF-8" ?>');

printf('<dokeosobject>');

printf('<courseobject>');
foreach ($_SESSION['_course'] as $key => $val)	printf('<%s>%s</%s>',$key,$val,$key);
printf('</courseobject>');

printf('<userobject>');
foreach ($_SESSION['_user'] as $key => $val)	printf('<%s>%s</%s>',$key,$val,$key);
printf('</userobject>');

printf('</dokeosobject>');
?>
