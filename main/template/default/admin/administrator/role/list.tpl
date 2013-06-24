{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    {% for item in items %}
         {{ item.name }} - {{ item.role}}
        <a class="btn" href="{{ url('admin_administrator_roles_edit', { id: item.id }) }}"> Edit</a>
        <a class="btn" href="{{ url('admin_administrator_roles_delete', { id: item.id }) }}"> Delete</a>
        <br />
    {% endfor %}

{% endblock %}
