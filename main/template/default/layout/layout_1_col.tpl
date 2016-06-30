{% extends template ~ "/layout/page.tpl" %}

{% block body %}
    {% if plugin_main_top %}
        <div id="plugin_main_top" class="col-md-12">
            {{ plugin_main_top }}
        </div>
    {% endif %}
    {% if plugin_content_top %}
        <div id="plugin_content_top" class="col-md-12">
            {{ plugin_content_top }}
        </div>
    {% endif %}

    <div class="col-xs-12 col-md-12">
        {% include template ~ "/layout/page_body.tpl" %}
        {% block content %}
            {% if content is not null %}
                <section id="main_content">
                {{ content }}
                </section>
            {% endif %}
        {% endblock %}
        &nbsp;
    </div>

    {% if plugin_content_bottom %}
        <div id="plugin_content_bottom" class="col-md-12">
            {{ plugin_content_bottom }}
        </div>
    {% endif %}

    {% if plugin_main_bottom %}
        <div id="plugin_main_bottom" class="col-md-12">
            {{ plugin_main_bottom }}
        </div>
    {% endif %}
{% endblock %}
