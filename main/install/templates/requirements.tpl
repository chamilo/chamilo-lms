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

    <div class="RequirementHeading">
        <h3> {{ 'ServerRequirements' | trans }}</h3>
    </div>

    <div class="RequirementText">{{ 'ServerRequirementsInfo' | trans }}</div>

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


    </table>



    {% autoescape false %}
        {{ requirements }}
    {% endautoescape %}

    <form action="#" method="post">
        {{ form_widget(form) }}
    </form>


{% endblock %}

