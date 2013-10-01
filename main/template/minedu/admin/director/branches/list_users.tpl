{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <div class="actions">
        <a href="{{ url('branch_director.controller:indexAction') }}">
            <i class="icon-circle-arrow-left icon-2x"></i>
        </a>
    </div>

    <h2>{{ 'Jury' | trans }} {{ jury.name }}</h2>
    <h3>{{ 'Members' | trans }}</h3>
    <table class="data_table">
        <tr>
            <th>{{ 'Username' | trans }}</th>
            <th>{{ 'Firstname' | trans }}</th>
            <th>{{ 'Lastname' | trans }}</th>
        </tr>
    {% for user in users %}
        <tr>
            <td>
                {{ user.user.username }}
            </td>
            <td>
                {{ user.user.firstname }}
            </td>
            <td>
                {{ user.user.lastname }}
            </td>
        </tr>
    {% endfor %}
    </table>

{% endblock %}
