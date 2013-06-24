{% extends 'layout.tpl' %}

{% block content %}
    <h3>Congratulations Chamilo is now installed!</h3>

    <a class="btn btn-success btn-large btn-install" href= "{{ app.request.basepath }}../../../index.php" autofocus="autofocus">
        GoToYourNewlyCreatedPortal
    </a>

    <br />
    <br />

    Output info:
    <br />
    <br />
    <pre>
    {% autoescape false %}
        {{ output }}
    {% endautoescape %}
    </pre>
{% endblock %}

