{% extends 'default/layout/layout_1_col.tpl' %}

{% block content %}
    {{ page|var_dump}}
    {{ page.title }}
    {{ page.slug }}
    {{ page.content }}
{% endblock %}