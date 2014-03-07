{% extends app.template_style ~ "/layout/main.tpl" %}

{#  1 column  #}
{% block body %}
    {#  Plugin top  #}
    {% if plugin_content_top %}
        <div id="plugin_content_top" class="col-lg-10 col-sm-11">
            {{ plugin_content_top}}
        </div>
    {% endif %}

    {% if app.template.show_header == true %}
        <div id="main_content" class="col-lg-10 col-sm-11">
    {% endif %}

    {# Breadcrumb #}
    {% include app.template_style ~ "/layout/breadcrumb.tpl" %}

    <div class="row">
        <div class="col-sm-12 col-md-12">
            {% include app.template_style ~ "/layout/page_body.tpl" %}

            {% block content %}
                {% if content is not null %}
                    <section id="section_content">
                    {{ content }}
                    </section>
                {% endif %}
            {% endblock %}

            {% if app.template.show_header == true %}
            </div>
            {% endif %}

            {#  Plugin bottom  #}
            {% if plugin_content_bottom %}
                <div id="plugin_content_bottom" class="col-md-12">
                    {{ plugin_content_bottom }}
                </div>
            {% endif %}
        </div>
    </div>

    {% if app.template.show_header == true %}
        </div>
    {% endif %}
{% endblock %}
