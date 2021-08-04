{% import 'default/macro/macro.tpl' as display %}
{% extends 'layout/layout_1_col.tpl'|get_template %}

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

                {% set highlight_revision = '' %}
                {% if post.is_a_revision %}
                    {% set highlight_revision = 'forum_revision' %}
                {% endif %}

                <div class="col-md-10 {{ highlight }} ">
                    {{ post.post_title }}

                    {% if post.is_a_revision %}
                       {{ 'ProposedRevision' | get_lang  }} {{ post.flag_revision }}
                    {% endif %}

                    <div class="{{ highlight_revision }} ">
                        {{ post.post_data }}
                    </div>

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

        {% if view_mode == 'nested' %}
            <div class="col-md-offset-{{ post.indent_cnt }} forum-post">
                {{ display.panel('', post_data) }}
            </div>
        {% else %}
            <div class="col-md-12 forum-post">
                {{ display.panel('', post_data) }}
            </div>
        {% endif %}
    {% endfor %}

    <div class="row">
        <div class="col-md-12">
            {{ form }}
        </div>
    </div>
{% endblock %}
