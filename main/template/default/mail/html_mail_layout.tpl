<html>
<head>
    <title>
        {% block subject %}
        {% endblock %}
    </title>
</head>
<body>
<header>
    <strong>Chamilo mail header!!</strong>
</header>
{% block body %}
    HTML <strong>body</strong>
{% endblock %}
<hr>
<footer>
    <strong>Chamilo mail footer!!</strong>
</footer>
</body>
</html>
