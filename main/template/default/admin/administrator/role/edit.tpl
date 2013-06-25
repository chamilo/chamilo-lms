{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    {% import app.template_style ~ "/default_actions/settings.tpl" as actions %}
    {{ actions.edit(form, links) }}
{% endblock %}
