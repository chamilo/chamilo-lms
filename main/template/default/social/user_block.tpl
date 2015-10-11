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
                        
                        <ul class="list-user-data">
                            <li class="item">
                                {{ user.complete_name }}
                            </li>
                            <li class="item">
                                <a href="{{ _p.web }}main/messages/new_message.php">
                                <img src="{{ "instant_message.png" | icon }}" alt="{{ "Email" | get_lang }}">
                                {{ user.email}}
                                </a>
                            </li>
                            <li class="item">
                                <a href="{{ vcard_user_link }}">
                                <img src="{{ "vcard.png" | icon(16) }}" alt="{{ "UserInfo" | get_lang }}" width="16" height="16">
                                {{ "UserInfo" | get_lang }}
                                </a>
                            </li>
                        {% if chat_enabled == 1 %}
                            <li class="item">
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
                        
                        {% if not profile_edition_link is empty %}
                        <li class="item">
                            <a class="btn link btn-sm btn-block" href="{{ profile_edition_link }}">
                            <i class="fa fa-edit"></i>{{ "EditProfile" | get_lang }}
                            </a>
                        </li>
                        {% endif %}
                        
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
                        

