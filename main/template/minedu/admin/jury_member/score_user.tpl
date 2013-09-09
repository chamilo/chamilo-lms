{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}

    <h3>{{ 'Calificar usuario' |trans }}</h3>
    <form action="{{ url('jury_member.controller:saveScoreAction') }}" method="post">
        <input name="exe_id" type="hidden" value="{{ exe_id }}"/>
        {{ exercise }}
        <button class="btn" type="submit">{{ 'Save' | trans}}</button>
    </form>

{% endblock %}
