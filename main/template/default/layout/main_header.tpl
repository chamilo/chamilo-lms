<!DOCTYPE html>
<!--[if lt IE 7]> <html lang="{$document_language}" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>    <html lang="{$document_language}" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>    <html lang="{$document_language}" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--><html lang="{$document_language}" class="no-js"> <!--<![endif]-->
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
                        {$notification_menu}
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
        {if $menu}
            <div id="header3" class="subnav">        
                {$menu} 
            </div>        
        {/if}

        {* breadcrumb *}
        {$breadcrumb}
        <div class="row">
            {if !empty($show_course_shortcut) }
                <div class="span12">
                    {$show_course_shortcut}
                </div>
            {/if}
            
            {if !empty($show_course_navigation_menu) }
                 <script type="text/javascript">
                     
                    /* <![CDATA[ */
                    $(document).ready( function() {
                        if (readCookie('menu_state') == 0) {
                            swap_menu();
                        }
                    });
                    
                    /* ]]> */

                    /* <![CDATA[ */
                    function createCookie(name, value, days) {
                        if (days) {
                            var date = new Date();
                            date.setTime(date.getTime()+(days*24*60*60*1000));
                            var expires = "; expires="+date.toGMTString();
                        }
                        else var expires = "";
                        document.cookie = name+"="+value+expires+"; path=/";
                    }
                    function readCookie(name) {
                        var nameEQ = name + "=";
                        var ca = document.cookie.split(';');
                        for (var i = 0; i < ca.length; i++)
                        {
                            var c = ca[i];
                            while (c.charAt(0)==' ') c = c.substring(1,c.length);
                            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
                        }
                        return null;
                    }
                    function swap_menu() {
                        toolnavlist_el = document.getElementById('toolnav');
                        center_el = document.getElementById('center');
                        swap_menu_link_el = document.getElementById('swap_menu_link');

                        if (toolnavlist_el.style.display == 'none') {
                            toolnavlist_el.style.display = '';
                            if (center_el) {
                                center_el.style.margin = '0 190px 0 0';
                            }
                            swap_menu_link_el.innerHTML = '{'Hide'|get_lang}';
                            createCookie('menu_state',1,10);
                        } else {
                            toolnavlist_el.style.display = 'none';
                            if (center_el) {
                                center_el.style.margin = '0 0 0 0';
                            }
                            swap_menu_link_el.innerHTML = '{'Show'|get_lang}';
                            createCookie('menu_state',0,10);
                        }
                    }
                    document.write('<div class="span12 pull-right"> <a class="btn" href="javascript: void(0);" id="swap_menu_link" onclick="javascript: swap_menu();">{'Hide'|get_lang}<\/a></div>');
                    /* ]]> */
                    </script>                    
                    {$show_course_navigation_menu}                    
            {/if}            
{/if}