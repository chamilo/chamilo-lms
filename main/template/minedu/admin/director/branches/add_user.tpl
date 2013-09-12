{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <form action="{{ url('branch_director.controller:addUsersAction', {'juryId' : juryId, 'branchId':branchId}) }}" method = "post" {{ form_enctype(form) }}>
    {{ form_widget(form) }}
    </form>
{% endblock %}
