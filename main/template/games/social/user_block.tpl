<div class="profile-user">
    <div class="username">{{ user.complete_name }}</div>
    {{ socialAvatarBlock }}
    <div class="email">
        <img src="{{ "instant_message.png" | icon }}" atl="{{ "Email" | get_lang }}">
        {{ user.email}}
    </div>
    <div class="points">189 Puntos</div>
</div>
