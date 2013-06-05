{% extends "default/mail/layout.tpl" %}

{% block subject %}
    {{ data.subject }}
{% endblock %}

{% block html_body %}
    <h1>{{ data.title }}</h1>
    <p>{{ data.content }}</p>
    {{ data.user }}
    <h1>{{ "ConfirmYourChoice" | get_lang }}</h1>
{% endblock %}

{% block text_body %}
    * {{ data.title }} *
    > {{ data.content }}
    :) {{ data.user }}
{% endblock %}
