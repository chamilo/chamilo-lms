{% extends 'layout/blank_no_header.tpl'|get_template %}

{% block body %}
    {% include 'diagram.tpl'%}
    {{ content }}
{% endblock %}