<div class="social-avatar">
    <div class="panel panel-default">
        {{ social_avatar_block }}
        <p class="lead">
            {{ user.firstname }}<br>{{ user.lastname }}
        </p>
        <ul class="list-unstyled user-details">
            <li>
                <a href="{{ vcard_user_link }}">
                    <img src="{{ "vcard.png" | icon(22) }}" alt="{{ "UserInfo" | get_lang }}">
                    {{ "UserInfo" | get_lang }}
                </a>
            </li>
            <li>
                <a href="{{ _p.web }}main/messages/new_message.php">
                    <img src="{{ "instant_message.png" | icon }}" alt="{{ "Email" | get_lang }}">
                    {{ user.email}}
                </a>
            </li>
            {% if chat_enabled == 1 %}
                <li>
                    {% if user.user_is_online_in_chat != 0 %}
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
            <p>
                <a class="btn btn-default btn-sm" href="{{ profile_edition_link }}">
                    <i class="fa fa-edit"></i>
                    {{ "EditProfile" | get_lang }}
                </a>
            </p>
        {% endif %}
    </div>
</div>

