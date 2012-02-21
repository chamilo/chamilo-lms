<!DOCTYPE html>
<!--[if lt IE 7]> <html lang="{$document_language}" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>    <html lang="{$document_language}" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>    <html lang="{$document_language}" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="{$document_language}" class="no-js"> <!--<![endif]-->
<head>    
{* <script type="text/javascript" src="http://fbug.googlecode.com/svn/lite/branches/firebug1.4/content/firebug-lite-dev.js"></script> *}
{include file="default/layout/head.tpl"}
</head>
<body dir="{$text_direction}" class="{$section_name}">
<noscript>{"NoJavascript"|get_lang}</noscript>
{if $show_header}
    
<div class="skip">
    <ul>
        <li><a href="#menu">{"WCAGGoMenu"|get_lang}</a></li>
        <li><a href="#content" accesskey="2">{"WCAGGoContent"|get_lang}</a></li>
    </ul>
</div>
    
<div id="wrapper">
    {* Bug and help notifications *}		
    <ul id="navigation" class="notification-panel">
        {$help_content}
        {$bug_notification_link}
    </ul>    
    
    {* topbar *}
    {include file="default/layout/topbar.tpl"}
    
    <div id="main" class="container">        
        <header> 
            {* header1 - logo *}
            <div id="header1">                
                {$header1}
            </div>            
            {$plugin_header}    

            {* header 2 - right menu (notifications) *}    
            <div id="header2">
                <ul class="nav nav-pills">        
                    {$header2}
                </ul>
            </div>
        </header>

        {* header 3 - menu *}
        {if $header3}
            <div id="header3" class="subnav">        
                {$header3}    
            </div>        
        {/if}

        {* breadcrumb *}
        {$breadcrumb}
        <div id="submain-content" class="row-fluid">
{/if}          