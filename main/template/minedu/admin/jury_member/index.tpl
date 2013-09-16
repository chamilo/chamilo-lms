{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <h3>{{ 'Users' |trans }}</h3>
    <a class="btn" href="{{ url('jury_member.controller:listUsersAction') }}">
        Revisar notas.
    </a>
{% endblock %}
