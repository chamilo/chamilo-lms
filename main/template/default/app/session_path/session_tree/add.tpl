{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    {% import app.template_style ~ "/crud_macros/simple_crud.tpl" as actions %}
    {{ actions.add(form, links) }}
{% endblock %}
