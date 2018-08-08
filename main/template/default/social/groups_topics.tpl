{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
    <div class="row">
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
            {{ group_message }}
            {{ social_right_content }}
        </div>
    </div>
{% endblock %}
