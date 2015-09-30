    <div class="sidebar-avatar">
        <div class="panel-group" id="sn-avatar" role="tablist" aria-multiselectable="true">
            <div class="panel panel-default">
                <div class="panel-heading" role="tab" id="heading-sn">
                    <h4 class="panel-title">
                        <a role="button" data-toggle="collapse" data-parent="#sn-avatar" href="#sn-avatar-one" aria-expanded="true" aria-controls="sn-avatar-one">
                        {{ "Role" | get_lang }}
                        </a>
                    </h4>
                </div>
                <div id="sn-avatar-one" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading-sn">
                    <div class="panel-body">
                        
                        {{ social_avatar_block }}
                        {{ user.complete_name }}
                        
                        <ul class="list-unstyled user-details">
                            <li>
                                <a href="{{ _p.web }}main/messages/new_message.php">
                                <img src="{{ "instant_message.png" | icon }}" alt="{{ "Email" | get_lang }}">
                                {{ user.email}}
                                </a>
                            </li>
                            <li>
                                <a href="{{ vcard_user_link }}">
                                <img src="{{ "vcard.png" | icon(22) }}" alt="{{ "UserInfo" | get_lang }}" width="22" height="22">
                                {{ "UserInfo" | get_lang }}
                                </a>
                            </li>
                        {% if chat_enabled == 1 %}
                            <li>
                                {% if user.id == _u.id %}
                                    <img src="{{ "online.png" | icon }}" alt="{{ "Online" | get_lang }}">
                                    {{ "Chat" | get_lang }} ({{ "Online" | get_lang }})
                                {% elseif user.user_is_online_in_chat != 0 %}
                                    <a onclick="javascript:chatWith('{{ user.id }}', '{{ user.complete_name }}', '{{ user.user_is_online }}','{{ user.avatar_small }}')" href="javascript:void(0);">
                                        <img src="{{ "online.png" | icon }}" alt="{{ "Online" | get_lang }}">
                                        {{ "Chat" | get_lang }} ({{ "Online" | get_lang }})
                                    </a>
                                {% else %}
                                    <img src="{{ "offline.png" | icon }}" alt="{{ "Online" | get_lang }}">
                                    {{ "Chat" | get_lang }} ({{ "Offline" | get_lang }})
                                {% endif %}
                            </li>
                        {% endif %}
                        </ul>
                        {% if not profile_edition_link is empty %}
                        <a class="btn btn-default btn-sm" href="{{ profile_edition_link }}">
                            <i class="fa fa-edit"></i>{{ "EditProfile" | get_lang }}
                        </a>
                        {% endif %}
                    </div>
                </div>
            </div>
        </div>
    </div>

