{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <h3>{{ 'Actas administrativas' |trans }}</h3>
    <a class="btn" href="{{ url('jury_president.controller:openJuryAction') }}">
        Actar apertura del comité
    </a>
    <a class="btn" href="{{ url('jury_president.controller:closeScoreAction') }}">
        Actar el cierre de las notas.
    </a>
    <a class="btn" href="{{ url('jury_president.controller:closeJuryAction') }}">
        Actar la finalización del proceso.
    </a>

    <h3>{{ 'Asignación de responsabilidades' |trans }}</h3>

    <a class="btn" href="{{ url('jury_president.controller:assignMembersAction') }}">
        Asignar usuarios.
    </a>

    <h3>{{ 'Revisión de respuestas' |trans }}</h3>

    <a class="btn" href="{{ url('jury_president.controller:assignMembersAction') }}">
        Revisar notas.
    </a>

{% endblock %}
