{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <div class="actions">
        <a href="{{ url('exercise_distribution.controller:indexAction', {'exerciseId' : exerciseId }) }}">
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
                    {{ item.average }}
                </td>
            </tr>
        {% endfor %}
    </table>
{% endblock %}
