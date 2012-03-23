{*
    show_header and show_footer templates are only called when using the Display::display_header and Display::display_footer
    for backward compatibility we suppose that the default layout is one column which means using a div with class span12
*}
{if $show_header}
    </section>
</div>
{/if}

{* Plugin bottom *}
{if !empty($plugin_content_bottom)}       
    <div id="plugin_content_bottom" class="span12">
        {$plugin_content_bottom}
    </div>
{/if}
{include file="default/layout/main_footer.tpl"}