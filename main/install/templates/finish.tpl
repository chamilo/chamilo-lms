{% extends 'layout.tpl' %}

{% block content %}
    <h3>Congratulations Chamilo is now installed!</h3>

    Output info:
    <br />
    <br />
    <pre>
    {% autoescape false %}
        {{ output }}
    {% endautoescape %}
    </pre>
{% endblock %}

