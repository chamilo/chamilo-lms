<div class="social-avatar">
    <div class="panel panel-default">
        {{ socialAvatarBlock }}
        <div class="social-avatar-name">
            <p class="firstname">{{ user.firstname }}</p>
            <p class="lastname">{{ user.lastname }}</p>
        </div>
        <div class="social-avatar-email">
            <a href="{{ _p.web }}main/messages/new_message.php">
                <img src="{{ "instant_message.png" | icon }}" atl="{{ "Email" | get_lang }}">
                {{ user.email}}
            </a>
        </div>
        <div class="social-avatar-vcard">
            <a href="{{ vCardUserLink }}">
                <img src="{{ "vcard.png" | icon(22) }}" atl="{{ "UserInfo" | get_lang }}">
                {{ "UserInfo" | get_lang }}
            </a>
        </div>
        {% if chat_enabled == 1 %}
            {% if user.user_is_online_in_chat != 0 %}
            <div class="social-avatar-chat">
                <a onclick="javascript:chatWith('{{ user.id }}', '{{ user.complete_name }}', '{{ user.user_is_online }}','{{ user.avatar_small }}')" href="javascript:void(0);"> 
                <img src="{{ "online.png" | icon }}" alt="{{ "Online" | get_lang }}">
                {{ "Chat" | get_lang }} ({{ "Online" | get_lang }})
                </a>
            </div>
            {% else %}
            <div class="social-avatar-chat">
                <img src="{{ "offline.png" | icon }}" alt="{{ "Online" | get_lang }}">
                {{ "Chat" | get_lang }} ({{ "Offline" | get_lang }})
            </div>
            {% endif %}
        {% endif %}
        {% if not profileEditionLink is empty %}
            <a class="btn btn-default" href="{{ profileEditionLink }}">
                <i class="fa fa-edit"></i>
                {{ "EditProfile" | get_lang }}
            </a>
        {% endif %}
    </div>
</div>

