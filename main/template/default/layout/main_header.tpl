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
            <div class="row">
                {* header1 - logo *}
                <div id="header_left" class="span4">                
                    {$header1}                    
                    {* plugin_header *}        
                    {if !empty($plugin_header_left)}
                        <div id="plugin_header_left">
                            {$plugin_header_left}
                        </div>
                    {/if}
                </div>
                
                <div id="header_center" class="span4">                
                    {* plugin_header *}        
                    {if !empty($plugin_header_center)}
                        <div id="plugin_header_center">
                            {$plugin_header_center}
                        </div>
                    {/if}
                    &nbsp;
                </div>                                
                <div id="header_right" class="span4">   
                     {* header right (notifications) *}    
                    <ul id="notifications" class="nav nav-pills pull-right">        
                        {$header2}
                    </ul>

                    {* plugin_header *}        
                    {if !empty($plugin_header_right)}
                        <div id="plugin_header_right">
                            {$plugin_header_right}
                        </div>
                    {/if}
                    &nbsp;
                </div>
            </div>
                
            {if !empty($plugin_header_main)}
                <div class="row">
                    <div class="span12">
                        <div id="plugin_header_main">
                            {$plugin_header_main}
                        </div>
                    </div>
                </div>
            {/if}
        </header>
        {* header 3 - menu *}
        {if $header3}
            <div id="header3" class="subnav">        
                {$header3}    
            </div>        
        {/if}

        {* breadcrumb *}
        {$breadcrumb}
        <div class="row">   
{/if}