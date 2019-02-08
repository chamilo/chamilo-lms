{% extends 'layout/layout_1_col.tpl'|get_template %}
{% import 'macro/macro.tpl'|get_template as display %}

{% block content %}
    {% if origin == 'learnpath' %}
        <div style="height:15px">&nbsp;</div>
    {% endif %}

    {% if forum_actions %}
        <div class="actions">
            {{ forum_actions }}
        </div>
    {% endif %}

    {% for post in posts %}
        {% set post_data %}
            <div class="row">
                <div class="col-md-2">
                    {{ post.user_data }}
                </div>
                {% set highlight = '' %}
                {% if post.current %}
                    {% set highlight = 'alert alert-danger' %}
                {% endif %}

                <div class="col-md-10 {{ highlight }}">
                    {{ post.post_data }}

                    {{ post.post_attachments }}
                </div>
            </div>
            <div class="row">
                <div class="col-md-4"></div>
                <div class="col-md-8 text-right">
                    {{ post.post_buttons }}
                </div>
            </div>
        {% endset %}

        <div class="col-md-offset-{{ post.indent_cnt }} forum-post">
            {{ display.panel('', post_data ) }}
        </div>
    {% endfor %}
{% endblock %}
