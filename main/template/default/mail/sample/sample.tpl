{% extends app.template_style ~ "/mail/html_mail_layout.tpl" %}

{% block subject %}
    {{ yolo.subject }}
{% endblock %}

{% block body %}
    {{ yolo.content }}
    {{ yolo.user }}
{% endblock %}
