{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}

{% block content %}
    {% if (app.request.get('exerciseId')) %}
        <a href="{{ url('exercise_dashboard', {cidReq : app.course_code, id_session:app.session_id, exerciseId: app.request.get('exerciseId') }) }}">
            <img src="{{ "back.png"|icon(22) }}">
        </a>
    {% endif  %}
    <h3>
        {{ question.question }}
        {% if (app.request.get('exerciseId')) %}
            <a href="{{ url('exercise_question_edit', { cidReq : app.course_code, id_session:app.session_id, id: question.id }) }}">
                <img src="{{ "edit.png"|icon(22) }}">
            </a>
        {% endif  %}
    </h3>

    {{ question_preview }}
{% endblock %}
