{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <a href="{{ url('exercise_distribution.controller:addDistributionAction', {'exerciseId' : exerciseId }) }}">
        {{ 'Add' |trans }}
    </a>
    <table class="table">
        {% for item in items %}
            <tr>
                <td>
                    <a href="{{ url('exercise_distribution.controller:readAction', { 'exerciseId' : exerciseId, 'id' : item.id }) }}">
                        {{ item.title }}
                    </a>
                </td>
                <td>
                    {{ item.active }}
                </td>
                <td>
                    <a class="btn" href="{{ url('exercise_distribution.controller:editDistributionAction',
                    { 'exerciseId' : exerciseId, id: item.id }) }}"> {{ 'Edit' |trans }}</a>
                    <a class="btn" href="{{ url('exercise_distribution.controller:toogleVisibilityAction',
                    { 'exerciseId' : exerciseId, id: item.id }) }}"> {{ 'Visible' |trans }}</a>
                    <a class="btn" href="{{ url('exercise_distribution.controller:applyDistributionAction',
                    { 'exerciseId' : exerciseId, id: item.id }) }}"> {{ 'Apply distribution' |trans }}</a>

                    <a class="btn" href="{{ url('exercise_distribution.controller:deleteDistributionAction',
                    { 'exerciseId' : exerciseId, id: item.id }) }}"> {{ 'Delete' |trans }}</a>
                </td>
            </tr>
        {% endfor %}
    </table>
{% endblock %}
