{% extends 'layout/no_layout.tpl'|get_template %}

{% block content %}
    {% include 'diagram.tpl'%}
    {{ content }}
{% endblock %}