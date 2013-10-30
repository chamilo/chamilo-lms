{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <div class="actions">
        <a href="{{ url('exercise_distribution.controller:indexAction', {'exerciseId' : exerciseId , 'cidReq':course.code, 'id_session' : course_session.id }) }}">
            {{ 'Back' |trans }}
        </a>
    </div>
    <table class="table">
        <th>{{ 'Distribution' | trans }}</th>
        <th>{{ 'Average' | trans }}</th>
        {% for item in items %}
            <tr>
                <td>
                    {{ item.title }}
                </td>
                <td>
                    {{ item.average | number_format(3) }}
                </td>
            </tr>
        {% endfor %}
    </table>
{% endblock %}
