{*
    show_header and show_footer templates are only called when using the Display::display_header and Display::display_footer
    for backward compatibility we suppose that the default layout is one column which means using a div with class span12
*}
{include file="default/layout/main_header.tpl"}
{if $show_header}
        {if !empty($plugin_content_top)}         
            <div id="plugin_content_top" class="span12">
                {$plugin_content_top}
            </div>
        {/if}        
        <div class="span12">
            <section id="main_content">
                {include file="default/layout/page_body.tpl"}
{/if}