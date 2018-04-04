{% extends 'layout/page.tpl'|get_template %}

{% block body %}
    {% if plugin_main_top %}
        <div class="row">
            <div id="plugin_main_top" class="col-md-12">
                {{ plugin_main_top }}
            </div>
        </div>
    {% endif %}
    {% if plugin_content_top %}
        <div class="row">
            <div id="plugin_content_top" class="col-md-12">
                {{ plugin_content_top }}
            </div>
        </div>
    {% endif %}

    <div class="row">
        <div class="col-xs-12 col-md-12">
            {% include 'layout/page_body.tpl'|get_template %}
            {% block content %}
                {% if content is not null %}
                    <section id="main_content">
                        {{ content }}
                    </section>
                {% endif %}
            {% endblock %}
        </div>
    </div>

    {% if plugin_content_bottom %}
        <div class="row">
            <div id="plugin_content_bottom" class="col-md-12">
                {{ plugin_content_bottom }}
            </div>
        </div>
    {% endif %}

    {% if plugin_main_bottom %}
        <div class="row">
            <div id="plugin_main_bottom" class="col-md-12">
                {{ plugin_main_bottom }}
            </div>
        </div>
    {% endif %}
{% endblock %}
