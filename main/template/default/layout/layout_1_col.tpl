{% extends app.template_style ~ "/layout/main.tpl" %}

{#  1 column  #}
{% block body %}

    {#  Plugin top  #}
    {% if plugin_content_top %}
        <div id="plugin_content_top" class="col-md-12">
            {{ plugin_content_top}}
        </div>
    {% endif %}

    {% if app.template.show_header == true %}
        <div class="col-lg-10 col-sm-11">
    {% endif %}

    {% include app.template_style ~ "/layout/page_body.tpl" %}

    {% block content %}
        {% if content is not null %}
            <section id="main_content">
            {{ content }}
            </section>
        {% endif %}
    {% endblock %}

    {% if app.template.show_header == true %}
        &nbsp;
    </div>
    {% endif %}

    {#  Plugin bottom  #}
    {% if plugin_content_bottom %}
        <div id="plugin_content_bottom" class="col-md-12">
            {{ plugin_content_bottom }}
        </div>
    {% endif %}

{% endblock %}
