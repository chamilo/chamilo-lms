{% set block_menu =  user_image_block %}
{% extends "@template_style/layout/block_menu.tpl" %}
{% block block_menu_item %}
    <div style="text-align:center;">
        {{ user_image_block.content }}
    </div>
{% endblock %}
