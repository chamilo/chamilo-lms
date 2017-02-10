{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
    <div class="row">
        <div class="col-md-3">
            {{ social_avatar_block }}
            <div class="social-network-menu">
            {{ social_menu_block }}
            </div>
        </div>
        <div class="col-md-6">
            {{ social_search_block }}
            {{ social_skill_block }}
            {{ social_group_block }}
            {{ social_right_content }}
            <div id="message_ajax_reponse" class=""></div>
            <div id="display_response_id"></div>
            {{ social_auto_extend_link }}
        </div>
        <div class="col-md-3">
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
