{% extends 'layout.tpl' %}

{% block content %}
    <h3> Requirements </h3>

    {% autoescape false %}
        {{ requirements }}
    {% endautoescape %}

    <form action="#" method="post">
        {{ form_widget(form) }}
    </form>


{% endblock %}

