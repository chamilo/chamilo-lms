{% extends 'layout.tpl' %}

{% block content %}
    Congratulations!


    Output info:
    <br />
    <pre>
    {% autoescape false %}
        {{ output }}
    {% endautoescape %}
    </pre>
{% endblock %}

