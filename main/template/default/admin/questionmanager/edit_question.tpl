{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <h3> {{ question.question }} </h3>
    {{ form }}
{% endblock %}
