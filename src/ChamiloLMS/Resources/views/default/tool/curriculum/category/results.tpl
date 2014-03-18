{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    {{ 'Results' | trans }}
    <hr />
    <ul>
    {% for user in users %}
        <li>
            {{ user.complete_name }} - {{ user.score }}
            <a href="{{ url('curriculum_user.controller:getUserItemsAction',
                { 'userId': user.user_id, 'course' : course.code, 'id_session' : course_session.id }) }}">
                {{ 'Details' | trans }}
            </a>
        </li>
    {% endfor %}
    </ul>

{% endblock %}
