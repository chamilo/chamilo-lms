{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
<div class="row">
    <div class="col-md-3">
        <div class="social-network-menu">
            {{ social_avatar_block }}
            {{ social_menu_block }}
        </div>
    </div>
    <div class="col-md-9" style="min-height:1px">
        <div class="row">
            <div class="col-md-12">
                <div id="message_ajax_reponse" class=""></div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                {{ search_form }}
            </div>
            {{ social_right_content }}
        </div>
    </div>
</div>

{% if formModals is defined %}
    {{ formModals }}
{% endif %}
{% endblock %}
