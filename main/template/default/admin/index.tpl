{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    {% for role in app.admin_toolbar_roles %}
        {% include app.template_style ~ "/admin/" ~ role ~ "/role_index.tpl" %}
    {% endfor %}
{% endblock %}
