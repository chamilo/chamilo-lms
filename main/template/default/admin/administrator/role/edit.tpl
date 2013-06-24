{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <form action="{{ path('admin_administrator_roles_edit')}}" method="post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>
{% endblock %}
