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
    
    {*topbar*}
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
        {$header4}
        <div id="submain-content" class="row">