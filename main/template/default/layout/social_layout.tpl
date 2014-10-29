{% extends "default/layout/layout_1_col.tpl" %}

{% block content %}
    <div class="row">
        <div class="span3">
            <div class="social-menu">
            {{ social_avatar_block }}
            {{ social_menu_block }}
            </div>
        </div>
        <div id="wallMessages" class="span5" style="min-height:1px">
            <div class="row">
                <div class="span5">
                    <div id="message_ajax_reponse" class=""></div>
                </div>
            </div>
            <div class="row">
                {{ social_right_content}}
            </div>
            <div id="display_response_id" class="span5"></div>
            {{ socialAutoExtendLink }}
        </div>
        <div class="span4">
            <div class="row">
                {{ socialRightInformation}}
            </div>
        </div>
    </div>
{% endblock %}