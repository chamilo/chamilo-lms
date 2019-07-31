{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
    <style>
        #listFriends .list-group {
            max-height: 250px;
            overflow-y:auto;
        }
    </style>
    <div class="row">
        <div class="col-md-3">
            {{ social_avatar_block }}

            <div class="social-network-menu">
            {{ social_menu_block }}
            </div>
        </div>
        <div class="col-md-6">
            <div id="wallMessages">
                {{ add_post_form }}
                <div class="spinner"></div>
                <div class="panel panel-preview panel-default" hidden="true">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ "Url" | get_lang }} - {{ "Preview" | get_lang }}</h3>
                    </div>
                    <div class="panel-body">
                        <div class="url_preview"></div>
                    </div>
                </div>
                {{ posts }}
                {{ social_auto_extend_link }}
            </div>

            {{ social_right_content }}
            <div id="message_ajax_reponse" class=""></div>
            <div id="display_response_id"></div>
        </div>
        <div class="col-md-3">
            {{ social_group_block }}
            <!-- Block chat list -->
            <div class="chat-friends">
                <div class="panel-group" id="blocklistFriends" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingOne">
                            <h4 class="panel-title">
                                <a role="button"
                                   data-toggle="collapse"
                                   data-parent="#blocklistFriends"
                                   href="#listFriends"
                                   aria-expanded="true"
                                   aria-controls="listFriends">
                                    {{ "SocialFriend" | get_lang }}
                                </a>
                            </h4>
                        </div>
                        <div id="listFriends"
                             class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
                            <div class="panel-body">
                                <div class="search-friend">
                                    {{ search_friends_form }}
                                </div>

                                {{ social_friend_block }}

                                {% if 'allow_social_map_fields'|api_get_configuration_value %}
                                <div class="geolocalization">
                                    <a class="btn btn-maps" id="profile-tab" href="{{ _p.web }}main/social/map.php" >
                                        {{ "geolocalization.png"|img(32) }}
                                        {{ 'SearchUserByGeolocalization' | get_lang }}
                                    </a>
                                </div>
                                {% endif %}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{ social_skill_block }}

            <!-- Block session list -->
            {% if session_list != null %}
            <div class="panel-group" id="session-block" role="tablist" aria-multiselectable="true">
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="headingOne">
                        <h4 class="panel-title">
                            <a role="button" data-toggle="collapse" data-parent="#session-block" href="#sessionList" aria-expanded="true" aria-controls="sessionList">
                               {{ "MySessions" | get_lang }}
                            </a>
                        </h4>
                    </div>
                    <div id="sessionList" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
                        <div class="panel-body">
                            <ul class="list-group">
                                {% for session in session_list %}
                                <li id="session_{{ session.id }}" class="list-group-item" style="min-height:65px;">
                                    <img class="img-session" src="{{ session.image }}"/>
                                    <span class="title">{{ session.name }}</span>
                                </li>
                                {% endfor %}
                            </ul>
                        </div>
                    </div>
                </div>
             </div>
             {% endif %}
        </div>
    </div>
{% endblock %}
