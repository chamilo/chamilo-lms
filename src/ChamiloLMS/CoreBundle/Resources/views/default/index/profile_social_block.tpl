{% set block_menu =  profile_social_block %}
{% extends "@template_style/layout/block_menu.tpl" %}
{% block block_menu_item %}
    <h4>{{ block_menu.title }}</h4>
    <ul class="nav nav-list">
        <li><a href="{{ _p.web }}main/messages/inbox.php?f=social">{{ 'Inbox' | trans }} {{ _u.messages_count}}</a></li>
        <li><a href="{{ _p.web }}main/messages/new_message.php?f=social"> {{ 'Compose' | trans }}</a></li>
        <li><a href="{{ _p.web }}main/social/invitations.php"> {{ 'PendingInvitations' | trans }} {{ _u.messages_invitations_count}}</a></li>
        <li><a href="{{ _p.web }}main/auth/profile.php">{{ 'EditProfile' | trans }}</a></li>

        {%  if ('allow_personal_user_files' | get_setting) == 'true' %}
            <li><a href="{{ url('profile.controller:fileAction', {'username' : _u.username}) }}">{{ 'MyFiles' | trans }}</a></li>
        {% endif %}

    </ul>
{% endblock %}
