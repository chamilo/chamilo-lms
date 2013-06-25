{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    {% import app.template_style ~ "/default_actions/settings.tpl" as actions %}
    {{ actions.read(item, links) }}
{% endblock %}
