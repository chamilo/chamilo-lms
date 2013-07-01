{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}

{% block content %}
<ul>
    <li>
        <a href="{{ url('question_score_name.controller:indexAction') }}">{{ 'Question names' |trans }}</a>
    </li>
    <li>
        <a href="{{ url('question_score.controller:indexAction') }}">{{ 'Question score name' |trans }}</a>
    </li>
    <li>
        <a href="{{ url('role.controller:indexAction') }}">{{ 'Roles' |trans }}</a>
    </li>
    <li>
        <a href="{{ url('branch.controller:indexAction') }}">{{ 'Branches' |trans }}</a>
    </li>
    <li>
        <a href="{{ url('jury.controller:indexAction') }}">{{ 'Juries' |trans }}</a>
    </li>
</ul>

{% endblock %}
