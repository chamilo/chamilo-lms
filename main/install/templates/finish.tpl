{% extends 'layout.tpl' %}

{% block content %}
    <h3> {{ 'Congratulations Chamilo is now installed!' | trans }} </h3>

    <a class="btn btn-success btn-large btn-install" href= "{{ app.request.basepath }}../../../index.php" autofocus="autofocus">
        {{ 'Go to your newly created portal'|trans }}
    </a>
    <br />
    <br />

    Output info:
    <br />
    <br />
    <pre>{% autoescape false %}{{ output }}{% endautoescape %}</pre>
{% endblock %}

