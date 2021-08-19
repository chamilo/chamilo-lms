{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
    {% set iframe = 0 %}
    {% include 'career/diagram.tpl' |get_template %}
{% endblock %}