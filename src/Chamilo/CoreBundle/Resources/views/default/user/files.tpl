{% extends "@template_style/layout/layout_1_col.tpl" %}

{% block content %}
    <h3>{{ 'MyFiles' | trans }}</h3>
    {{ editor }}

{% endblock %}
