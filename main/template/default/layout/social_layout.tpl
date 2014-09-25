{% extends "default/layout/layout_1_col.tpl" %}

{% block content %}
    <div class="row">
        <div class="span3">
            <div class="social-menu">
            {{ social_avatar_block }}
            {{ social_menu_block }}
            </div>
        </div>
        <div class="span9">
            <div class="row">
                <span id="message_ajax_reponse" class="span9"></span>
                {{ social_right_content}}
                <div id="display_response_id" class="span9"></div>
            </div>
        </div>
    </div>
{% endblock %}