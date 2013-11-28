{% extends 'layout.tpl' %}

{% block content %}
    <h3> {{ 'Settings' | trans }} </h3>
<form action="#" method="post">
    {{ form_widget(form) }}
</form>
{% endblock %}
