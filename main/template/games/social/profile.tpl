{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
<div class="row">
    <div class="col-md-3">
        {{ social_avatar_block }}
        {{ social_extra_info_block }}
        {{ social_friend_block }}
        <div class="social-menu">
            {{ social_menu_block }}
        </div>
    </div>
    <div id="wallMessages" class="col-md-6">
        {{ social_wall_block }}
        {{ social_post_wall_block }}
        {{ social_auto_extend_link }}
    </div>
    <div class="col-md-3">
        {{ social_skill_block }}
        {{ social_group_info_block }}
        {{ social_course_block }}
        {{ social_session_block }}
        {{ social_rss_block }}
        {{ social_right_information }}
    </div>
</div>
    {% if formModals is defined %}
        {{ formModals }}
    {% endif %}
{% endblock %}
