<!DOCTYPE html>
<html>    
<head>
    {include file="default/layout/head.tpl"}
</head>
<body dir="{$text_direction}" class="{$section_name}">

<div class="skip">
	<ul>
		<li><a href="#menu">{"WCAGGoMenu"|get_lang}</a></li>
		<li><a href="#content" accesskey="2">{"WCAGGoContent"|get_lang}</a></li>
	</ul>
</div>
<div id="wrapper">
    {* Bug and help notifications *}		
    <ul id="navigation">
        {$help_content}
        {$bug_notification_link}
    </ul>    
    {include file="default/layout/header.tpl"}

    <div id="main">
        {* breadcrumb *}
        {$header4}
        <div id="submain">	    