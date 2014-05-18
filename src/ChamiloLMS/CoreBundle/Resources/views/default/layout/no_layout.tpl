{% include "@template_style/layout/head.tpl" %}
<body dir="{{text_direction}}" class="{{section_name}} no-sidebar">
{% block name %}
{% endblock %}
{% block body %}
{{ content }}
{% endblock %}
</body>
</html>
