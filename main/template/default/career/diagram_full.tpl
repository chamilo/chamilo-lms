{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
    {% include 'diagram.tpl'%}
    {{ content }}
{% endblock %}