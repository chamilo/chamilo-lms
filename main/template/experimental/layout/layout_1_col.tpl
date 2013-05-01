{% extends app.template_style ~ "/layout/main.tpl" %}

{#  1 column  #}
{% block body %}
    <h1>My custom template!!!</h1>

    {#  Plugin top  #}
    {% if plugin_content_top %}
        <div id="plugin_content_top" class="span12">
            {{ plugin_content_top}}
        </div>
    {% endif %}

    {% if show_header == true %}
        <div class="span12">
    {% endif %}

    {% include app.template_style ~ "/layout/page_body.tpl" %}

    {% block content %}
        {% if content is not null %}
            <section id="main_content">
                {{ content }}
            </section>
        {% endif %}
    {% endblock %}

    {% if show_header == true %}
        &nbsp;
        </div>
    {% endif %}


    {#  Plugin bottom  #}
    {% if plugin_content_bottom %}
        <div id="plugin_content_bottom" class="span12">
            {{ plugin_content_bottom }}
        </div>
    {% endif %}
{% endblock %}