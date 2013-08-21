{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <a href="{{ url(links.create_link, {'course' : course.code }) }}">
        {{ 'Add' |trans }}
    </a>
    {{ tree }}
{% endblock %}
