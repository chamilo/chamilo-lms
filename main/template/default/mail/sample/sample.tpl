{% extends "default/mail/layout.tpl" %}

{% block subject %}
    {{ yolo.subject }}
{% endblock %}

{% block html_body %}
    <h1>{{ yolo.title }}</h1>
    <p>{{ yolo.content }}</p>
    {{ yolo.user }}
{% endblock %}

{% block text_body %}
    * {{ yolo.title }} *
    > {{ yolo.content }}
    :) {{ yolo.user }}
{% endblock %}
