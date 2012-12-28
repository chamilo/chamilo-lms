{% extends "default/layout/layout_1_col.tpl" %}

{% block content %}
    {% for page in pages %}
        {{ page.title }} -
        {{ page.slug }} -
        {{ page.description }} - <a href="{{ url('edit', {id: page.id}) }}">Edit</a> <a href="{{ url('delete', {id: page.id}) }}">Delete</a>
        {{ loop.index }}/{{ loop.length }}
        <br />
    {% endfor %}
    {{ pagination }}
{% endblock %}