{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
    <div class="row">
        <div class="col-md-3">
            {{ social_avatar_block }}

            <div class="social-menu">
            {{ social_menu_block }}
            </div>
        </div>
        <div class="col-md-9">
            {{ social_search_block }}
            {{ social_skill_block }}
            {{ social_group_block }}
            {{ social_right_content }}
            <div id="message_ajax_reponse" class=""></div>
            <div id="display_response_id"></div>
            {{ social_auto_extend_link }}
        </div>
    </div>
{% endblock %}
