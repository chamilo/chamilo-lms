{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <a href="{{ url(links.create_link) }}">
        Add
    </a>
    {{ tree }}
{% endblock %}
