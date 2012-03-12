{*
    show_header and show_footer templates are only called when using the Display::display_header and Display::display_footer
    for backward compatibility we suppose that the default layout is one column which means ausing a div with class span12
*}
{if $show_header}
</div>
{/if}
{* Content bottom *}

{if !empty($plugin_content_bottom)}   
    <div class="clear"></div>
    <div id="plugin_content_bottom">
        {$plugin_content_bottom}
    </div>
{/if}
{include file="default/layout/main_footer.tpl"}