{% set block_menu =  profile_social_block %}
{% extends "default/layout/block_menu.tpl" %}
{% block block_menu_item %}
    <h4>{{ block_menu.title }}</h4>
    <ul class="nav nav-list">
        <li><a href="{{ _p.web}}main/messages/inbox.php?f=social">{{ 'Inbox' | get_lang}} {{ _u.messages_count}}</a></li>
        <li><a href="{{ _p.web}}main/messages/new_message.php?f=social"> {{ 'Compose' | get_lang}}</a></li>
        <li><a href="{{ _p.web}}main/social/invitations.php"> {{ 'PendingInvitations' | get_lang }} {{ _u.messages_invitations_count}}</a></li>
        <li><a href="{{ _p.web }}main/auth/profile.php">{{ 'EditProfile' | get_lang }}</a></li>
    </ul>
{% endblock %}