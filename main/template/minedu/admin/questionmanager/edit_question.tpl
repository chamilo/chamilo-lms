{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <h2>{{  'EditQuestion' | get_lang }}</h2>
    <h3> {{ question.question }} </h3>

    {{ form }}

    <h2>{{ 'Preview' | get_lang }}</h2>
    {{ question_preview }}
{% endblock %}
