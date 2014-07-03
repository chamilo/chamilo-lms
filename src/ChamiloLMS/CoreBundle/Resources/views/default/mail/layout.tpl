{% block subject %}
    You have new email!
{% endblock %}

{% block html_template %}
    <html>
    <head>
    </head>
    <body>
    <header>
        <strong>Chamilo mail header!!</strong>
    </header>
    {% block html_body %}
        HTML <strong>body</strong>
    {% endblock %}
    <hr>
    <footer>
        <strong>Chamilo mail footer!!</strong>
    </footer>
    </body>
    </html>
{% endblock %}

{% block text_template %}
    Chamilo mail header in plain TXT

    {% block text_body %}
        txt body
    {% endblock %}

    ----------------------------------------
    Chamilo mail footer in plain TXT
{% endblock %}
