{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}

    <a href="{{ url('exercise_distribution.controller:indexAction', {'exerciseId' : exerciseId }) }}">
        {{ 'List' |trans }}
    </a>
    <hr />
    <form action="{{ url('exercise_distribution.controller:addDistributionAction', {'exerciseId' : exerciseId }) }}" method="post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>
{% endblock %}
