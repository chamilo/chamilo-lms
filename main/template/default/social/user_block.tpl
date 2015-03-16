<div class="panel panel-info social-avatar">
    {{ socialAvatarBlock }}
    <p class="lead">{{ user.complete_name }}</p>
    <p>
        <img src="{{ "instant_message.png" | icon }}" atl="{{ "Email" | get_lang }}">
        {{ user.email}}  
    </p>
    {% if not user.extra.user_chat_status is empty %}
        <p>
            <img src="{{ "online.png" | icon }}" alt="{{ "Online" | get_lang }}">
            {{ "Chat" | get_lang }} ({{ "Online" | get_lang }})
        </p>
    {% else %}
        <p>
            <img src="{{ "offline.png" | icon }}" alt="{{ "Online" | get_lang }}">
            {{ "Chat" | get_lang }} ({{ "Offline" | get_lang }})
        </p>
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
