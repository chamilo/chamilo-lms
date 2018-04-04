{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
<div class="row">
    <div class="col-md-3">
        <div class="social-network-menu">
            {{ social_avatar_block }}
            {{ social_menu_block }}
        </div>
    </div>
    <div class="col-md-9">
        <div class="form-search-sm">
            {{ search_form }}
        </div>
        <div class="list-search-sm">
            {{ social_search }}
        </div>
    </div>
</div>

{% if form_modals is defined %}
    {{ form_modals }}
{% endif %}
{% endblock %}
