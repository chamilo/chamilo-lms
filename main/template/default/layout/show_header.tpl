{% include "default/layout/main_header.tpl" %}
{#
    show_header and show_footer templates are only called when using the Display::display_header and Display::display_footer
    for backward compatibility we suppose that the default layout is one column which means using a div with class span12
#}

{% if show_header == true %}
        {% if plugin_content_top is not null %}
            <div id="plugin_content_top" class="span12">
                {{ plugin_content_top }}
            </div>
        {% endif %}
        <div class="span12">
            {% include "default/layout/page_body.tpl" %}
            <section id="main_content">
{% endif %}