<html>
<head>
    <title>Chamilo Installation</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <link rel="stylesheet" href="{{ app.request.basepath }}/../../web/ChamiloLMS/js/bootstrap/css/bootstrap.css">
    <script type="text/javascript" src="{{ app.request.basepath }}/../../web/ChamiloLMS/js/jquery.js"></script>
    <script type="text/javascript" src="{{ app.request.basepath }}/../../web/ChamiloLMS/js/bootstrap/js/bootstrap.js"></script>
</head>

<body>
    <div class="container">
        <div id="main" class="container">
            <h2>Chamilo Installation</h2>

            {% set alertTypeAvaillable = [ 'info', 'success', 'warning', 'error'] %}
            {% for alert in alertTypeAvaillable %}
                {% for message in app.session.getFlashBag.get(alert) %}
                    <div class="alert alert-{{ alert }}" >
                        <button class="close" data-dismiss="alert">×</button>
                        {{ message|trans }}
                    </div>
                {% endfor %}
            {% endfor %}

            {% block content %}
            {% endblock %}
        </div>
    </div>
</body>
</html>
