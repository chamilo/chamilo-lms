{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}

{% block content %}
<ul>
    <li>
        <a href="{{ url('question_score_name.controller:indexAction') }}">Question names</a>
    </li>
    <li>
        <a href="{{ url('question_score.controller:indexAction') }}">Question score name</a>
    </li>
    <li>
        <a href="{{ url('role.controller:indexAction') }}">Roles</a>
    </li>
    <li>
        <a href="{{ url('admin_administrator_branches') }}">Branches</a>
    </li>
</ul>

{% endblock %}
