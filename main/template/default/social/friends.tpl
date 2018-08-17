{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
<div class="row">
    <div class="col-md-3">
        <div class="social-network-menu">
            {{ social_avatar_block }}
            {{ social_menu_block }}
        </div>
    </div>
    <div id="wallMessages" class="col-md-9" style="min-height:1px">
        <div class="row">
            <div class="col-md-12">
                <div id="message_ajax_reponse" class=""></div>
            </div>
        </div>
        <div class="row">
            {{ social_right_content}}
        </div>
        <div id="display_response_id" class="span5"></div>
        {{ social_auto_extend_link }}
    </div>
    <div class="col-md-4">
        <div class="row">
            {{ social_right_information }}
        </div>
    </div>
</div>
{% endblock %}
