{% extends 'default/layout/layout_1_col.tpl' %}
{% block content %}
    {{ error_code }} - {{ error_message }}
    {{ content }}
{% endblock %}