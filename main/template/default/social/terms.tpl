{% extends 'layout/layout_1_col.tpl'|get_template %}
{% import 'default/macro/macro.tpl' as display %}

{% block content %}
<div class="row">
    <div class="col-md-3">
        <div class="social-network-menu">
            {{ social_avatar_block }}
            {{ social_menu_block }}
        </div>
    </div>
    <div class="col-md-9">
        {% if term %}
            {{ display.panel('TermsAndConditions'|get_lang, term.content, term.date_text ) }}
        {% endif %}
    </div>
</div>
{% endblock %}
