{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}

{% block content %}
<ul>
    <li>
        <a href="{{ url('admin_administrator_question_scores') }}">Question score name</a>
    </li>
    <li>
        <a href="{{ url('admin_administrator_question_score_names') }}">Question names</a>
    </li>
    <li>
        <a href="{{ url('admin_administrator_roles') }}">Roles</a>
    </li>
</ul>

{% endblock %}
