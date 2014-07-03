{% extends "@template_style/layout/layout_1_col.tpl" %}

{% block content %}
    <div class="row">
        <div class="col-md-3">
            {{ social_left_content }}
            {{ social_left_menu }}
        </div>
        <div class="col-md-9">
            <div class="row">
                <span id="message_ajax_reponse" class="col-md-9"></span>
                {{ social_right_content }}
                <div id="display_response_id" class="col-md-9"></div>
            </div>
        </div>
    </div>
{% endblock %}
