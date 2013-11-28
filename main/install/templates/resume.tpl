{% extends 'layout.tpl' %}

{% block content %}
    <h3> {{ 'Resume' | trans }} </h3>
    <h3> {{ 'Database settings' | trans }} </h3>
    <table class="table table-striped">
    {% for key, value in database_settings %}
        <tr>
            <td> {{ key  }}</td>
            <td> {{ value }} </td>
        </tr>
    {% endfor %}
    </table>

    <h3> {{ 'Portal settings' | trans }} </h3>
    <table class="table table-striped">
    {% for key, value in portal_settings %}
        <tr>
            <td> {{ key  }}</td>
            <td> {{ value }} </td>
        </tr>
    {% endfor %}
    </table>

    <h3> {{ 'Admin settings' | trans }} </h3>
    <table class="table table-striped">
    {% for key, value in admin_settings %}
        <tr>
            <td> {{ key  }}</td>
            <td> {{ value }} </td>
        </tr>
    {% endfor %}
    </table>


<form action="#" method="post">
    {{ form_widget(form) }}
</form>
{% endblock %}

