{*
    show_header and show_footer templates are only called when using the Display::display_header and Display::display_footer
    for backward compatibility we suppose that the default layout is one column which means ausing a div with class span12
*}
{include file="default/layout/main_header.tpl"}
{if $show_header}
<div class="span12">
{/if}