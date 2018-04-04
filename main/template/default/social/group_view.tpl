{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
<div class="row" xmlns="http://www.w3.org/1999/html">
    <div class="col-md-3">
        <div class="sm-groups">
            {{ social_avatar_block }}
            {{ social_menu_block }}
            
            <!-- Block chat list -->    
            <div class="chat-friends">
                <div class="panel-group" id="blocklistFriends" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingOne">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#blocklistFriends" href="#listFriends" aria-expanded="true" aria-controls="listFriends">
                                    {{ "SocialFriend" | get_lang }}
                                </a>
                            </h4>
                        </div>
                        <div id="listFriends" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
                            <div class="panel-body">
                                {{ social_friend_block }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    <div class="col-md-9">
        <div class="sm-groups-content">
        {{ create_link }}
        
        {% if is_group_member == false %}
            <div class="social-group-details-info">
                {{ 'Privacy' | get_lang }}

                {% if group_info.visibility == 1 %}
                    {{ 'ThisIsAnOpenGroup' | get_lang }}
                {% else %}
                    {{ 'ThisIsACloseGroup' | get_lang }}
                {% endif %}
            </div>
        {% endif %}
        
        <div class="group-info">
            <h2 class="title">{{ group_info.name }}</h2>
            <p class="description">{{ group_info.description }}</p>
        </div>
        <div class="group-list">
            {{ social_forum }}
        </div>
        
        {{ social_right_content }}

        <div id="display_response_id" class="col-md-5"></div>
        {{ social_auto_extend_link }}
        </div>
    </div>
</div>
{% endblock %}
