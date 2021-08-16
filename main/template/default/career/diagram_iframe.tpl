{% extends 'layout/no_layout.tpl'|get_template %}

{% block body %}
    {% set iframe = 1 %}
    {% include 'career/diagram.tpl' |get_template %}
{% endblock %}