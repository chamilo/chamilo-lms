{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <a class="btn" href="{{ url('jury_president.controller:openJuryAction') }}">
        Actar apertura
    </a>
    <a class="btn" href="{{ url('jury_president.controller:closeJuryAction') }}">
        Actar la finalización del proceso.
    </a>
    <a class="btn" href="{{ url('jury_president.controller:closeScoreAction') }}">
        Actar el cierre de las notas.
    </a>

    <a class="btn" href="{{ url('jury_president.controller:assignMembersAction') }}">
        Asignar usuarios.
    </a>

    <a class="btn" href="{{ url('jury_president.controller:assignMembersAction') }}">
        Revisar notas.
    </a>

{% endblock %}
