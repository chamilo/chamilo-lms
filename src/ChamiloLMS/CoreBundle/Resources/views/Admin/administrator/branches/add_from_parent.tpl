{% extends "@template_style/layout/layout_1_col.tpl" %}
{% block content %}
    <a href="{{ url(links.list_link) }}">
        List
    </a>
    <hr />
    <form action="{{ url(links.add_from_parent_link, { "id" : parent_id }) }}" method="post" {{ form_enctype(form) }}>
        {{ form_widget(form) }}
    </form>
{% endblock %}
