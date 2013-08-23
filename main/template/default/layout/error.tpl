{% extends app.template_style ~ '/layout/layout_1_col.tpl' %}
{% block content %}
    {{ error.code }} - {{ error.message }}
    {{ content }}
{% endblock %}
