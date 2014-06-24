{% set block_menu =  help_block %}
{% extends "@template_style/layout/block_menu.tpl" %}
{% block block_menu_item %}
    <h4>{{ block_menu.title }}</h4>
    <ul class="nav nav-list">
        {{ block_menu.content }}</ul>
    </ul>
{% endblock %}
