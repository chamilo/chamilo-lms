{% extends 'layout/layout_1_col.tpl'|get_template %}
{% import 'default/macro/macro.tpl' as display %}

{% block content %}
    <div class="col-md-12">
        {% if term %}
            {{ display.panel('TermsAndConditions'|get_lang, term.content, term.date_text ) }}
        {% endif %}
    </div>
{% endblock %}
