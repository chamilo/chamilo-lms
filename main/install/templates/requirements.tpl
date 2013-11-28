{% extends 'layout.tpl' %}

{% block content %}
    <h3> {{ 'Requirements' | trans }} </h3>

    <div>
        {{ 'ReadThoroughly' | trans }}
    </div>

    {{ 'MoreDetails' | trans }}
    <a href="{{ app.request.baseUrl }}../../../documentation/installation_guide.html" target="_blank">
        {{ 'ReadTheInstallGuide' | trans }}
    </a>

    <h3> {{ 'ServerRequirements' | trans }}</h3>

    <table class="table">
        <tr>
            <td>{{ 'PHPVersion' | trans }} >=  {{ required_php_version }} </td>
            <td>
            {% if required_php_version_validation  %}
                <strong><font color="red">{{ 'PHPVersionError' | trans }}</font></strong>
            {% else %}
                <strong><font color="green">{{ 'PHPVersionOK' | trans }} {{ php_version }}</font></strong>
            {% endif %}
            </td>
        </tr>

        {% autoescape false %}
        {{ requirements }}
        {% endautoescape %}
    </table>


    <h3> {{ 'PHPSettings' | trans }}</h3>

    <table class="table">
        <tr>
            <td>{{ 'Name' | trans }}</td>
            <td>{{ 'Recommended' | trans }}</td>
            <td>{{ 'Current' | trans }}</td>
        </tr>

        {% autoescape false %}
        {{ options }}
        {% endautoescape %}
    </table>

    <h3> {{ 'DirectoryAndFilePermissions' | trans }}</h3>

    {% autoescape false %}
    {{ permissions }}
    {% endautoescape %}

    <form action="#" method="post">
        {{ form_widget(form) }}
    </form>
{% endblock %}
