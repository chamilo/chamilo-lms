{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
<div class="row">
    <div class="col-md-3">
        <div class="social-network-menu">
            {{ social_avatar_block }}
            {{ social_menu_block }}
        </div>
    </div>
    <div class="col-md-9">
        {{ social_right_content }}
    </div>
</div>
{% endblock %}
