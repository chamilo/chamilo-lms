{% extends "default/layout/layout_2_col.tpl" %}

{% block header %}
    {% embed "default/layout/main_header.tpl" %}
        {% block head %}
            {{ parent() }}
        {% endblock %}

        {% block help_notifications %}
            {{ parent() }}
        {% endblock %}

        {% block logo %}
            {{ parent() }}
        {% endblock %}

        {% block breadcrumb %}
            {{ parent() }}
        {% endblock %}

        {% block menu %}
            {{ parent() }}
        {% endblock %}

        {% block topbar %}
            {{ parent() }}
        {% endblock %}
    {% endembed %}
{% endblock %}

{% block login_form %}
    {{ parent() }}
{% endblock %}

{% block page_body %}
    {{ parent() }}
{% endblock %}

{% block content %}
    {{ parent() }}
{% endblock %}
