{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}

    <h3>{{ 'Calificar usuario' |trans }}</h3>
    <form action="{{ url('jury_member.controller:saveScoreAction', {'juryId': jury_id, 'exeId' : exe_id }) }}" method="post">
        {{ exercise }}
        <button class="btn" type="submit">{{ 'Save' | trans}}</button>
    </form>

{% endblock %}
