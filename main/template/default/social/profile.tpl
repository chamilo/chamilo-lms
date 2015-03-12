{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
<div class="row">
    <div class="col-md-3">
        {{ social_avatar_block }}
        {{ social_extra_info_block }}
        <div class="social-menu">
            {{ social_menu_block }}
        </div>
    </div>
    <div id="wallMessages" class="col-md-6">
        {{ social_wall_block }}
        {{ social_post_wall_block }}
        {{ socialAutoExtendLink }}
       <!-- <div class="row">
            <div class="span5">
                <div id="message_ajax_reponse" class=""></div>
            </div>
        </div> -->

       <!--- <div id="display_response_id" class="span5"></div> -->

    </div>
    <div class="col-md-3">

            {{ social_skill_block }}
            {{ social_group_info_block }}
            {{ social_course_block }}
            {{ social_session_block }}
            {{ social_rss_block }}
            {{ socialRightInformation}}

    </div>
</div>
{% endblock %}