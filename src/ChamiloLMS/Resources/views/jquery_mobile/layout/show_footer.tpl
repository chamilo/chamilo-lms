{#
    show_header and show_footer templates are only called when using the Display::display_header and Display::display_footer
    for backward compatibility we suppose that the default layout is one column which means using a div with class span12
 #}
{# Load the template basis from the default template #}
{% extends app.template_style ~ "/../default/layout/show_footer.tpl" %}

