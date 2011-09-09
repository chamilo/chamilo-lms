<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{$document_language}" lang="{$document_language}">
<head>
{include file="default/layout/head.tpl"}
</head>

<body dir="{$text_direction}">

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
	{block name="header"}{/block}
	<div id="main">
		<div id="submain">			
			{block name="body"}{/block}
			<div class="clear">&nbsp;</div> <!-- 'clearing' div to make sure that footer stays below the main and right column sections -->
		</div> <!-- end of #main" started at the end of banner.inc.php -->
	</div> <!-- end of #main" started at the end of banner.inc.php -->
	<div class="push"></div>
</div> <!-- end of #wrapper section -->
{block name="footer"}{/block}
</body>
</html>