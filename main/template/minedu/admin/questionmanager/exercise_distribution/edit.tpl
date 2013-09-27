{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}

    <a href="{{ url('exercise_distribution.controller:indexAction', {'exerciseId' : exerciseId }) }}">
        {{ 'List' |trans }}
    </a>
    <hr />
    <form action="{{ url('exercise_distribution.controller:editDistributionAction', {'exerciseId' : exerciseId, 'id' : id }) }}" method="post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>
{% endblock %}
