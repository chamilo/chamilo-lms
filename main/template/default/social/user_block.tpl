<div class="social-avatar">
    <div class="panel panel-default">
        {{ socialAvatarBlock }}
        <div class="social-avatar-name">
            <p class="firstname">{{ user.firstname }}</p>
            <p class="lastname">{{ user.lastname }}</p>
        </div>
        <div class="social-avatar-email">
            <img src="{{ "instant_message.png" | icon }}" atl="{{ "Email" | get_lang }}">
            {{ user.email}}
        </div>
        {% if user.user_is_online_in_chat != 0 %}
        <div class="social-avatar-chat">
            <img src="{{ "online.png" | icon }}" alt="{{ "Online" | get_lang }}">
            {{ "Chat" | get_lang }} ({{ "Online" | get_lang }})
        </div>
        {% else %}
        <div class="social-avatar-chat">
            <img src="{{ "offline.png" | icon }}" alt="{{ "Online" | get_lang }}">
            {{ "Chat" | get_lang }} ({{ "Offline" | get_lang }})
        </div>
        {% endif %}

        {% if not profileEditionLink is empty %}
        <p>
            <a class="btn btn-link" href="{{ profileEditionLink }}">
                <i class="fa fa-edit"></i>
                {{ "EditProfile" | get_lang }}
            </a>
        </p>
        {% endif %}
    </div>
</div>

