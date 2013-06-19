{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <form action="{{ url('admin_administrator_roles_edit', {id : role.id}) }}" method = "post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>
{% endblock %}
