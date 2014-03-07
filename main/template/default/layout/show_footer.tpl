{#
    show_header and show_footer templates are only called when using the Display::display_header and Display::display_footer
    for backward compatibility we suppose that the default layout is one column which means using a div with class span12
#}
{% if app.template.show_header == true %}
    </section>
</div>
{% endif %}

{# Plugin bottom  #}
{% if plugin_content_bottom is not null %}
    <div id="plugin_content_bottom" class="col-md-12">
        {{ plugin_content_bottom}}
    </div>
{% endif %}

{% if show_footer == true %}
        </div> <!-- end of #row" -->
    </div> <!-- end of #main" -->
</div> <!-- end of #wrapper section -->
{% endif %}

{% include app.template_style ~ "/layout/main_footer.tpl" %}
