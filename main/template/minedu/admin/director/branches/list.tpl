{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <h2>{{ 'Branches'  | get_lang }}</h2>
    {{ tree }}
{% endblock %}
