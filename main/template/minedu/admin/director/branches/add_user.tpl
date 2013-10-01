{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <div class="actions">
        <a href="{{ url('branch_director.controller:indexAction') }}">
            <i class="icon-circle-arrow-left icon-2x"></i>
        </a>
    </div>
    <h2>{{ 'AddUsers' | trans }}</h2>
    <form action="{{ url('branch_director.controller:addUsersAction', {'juryId' : juryId, 'branchId':branchId}) }}" method = "post" {{ form_enctype(form) }}>
    {{ form_widget(form) }}
    </form>
{% endblock %}
