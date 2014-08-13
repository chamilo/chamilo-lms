{% extends "@template_style/layout/layout_1_col.tpl" %}
{% block content %}
    <a class="btn btn-default" href="{{ url(links.create_link) }}">
        {{ 'Add' | trans }}
    </a>
    {{ tree }}
{% endblock %}
