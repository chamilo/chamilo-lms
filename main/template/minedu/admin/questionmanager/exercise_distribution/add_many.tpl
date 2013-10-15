{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <a href="{{ url('exercise_distribution.controller:indexAction', {'exerciseId' : exerciseId, 'cidReq':course.code, 'id_session' : course_session.id }) }}">
        {{ 'List' |trans }}
    </a>
    <hr />
    <form
        action = "{{ url('exercise_distribution.controller:addManyDistributionAction', {'exerciseId' : exerciseId, 'cidReq':course.code, 'id_session' : course_session.id }) }}"
        method = "post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>
{% endblock %}
