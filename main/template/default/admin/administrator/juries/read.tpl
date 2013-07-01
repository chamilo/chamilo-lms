{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <a href="{{ url(links.list_link) }}">
        {{ 'List' |trans }}
    </a>
    <a href="{{ url(links.update_link, {id : item.id}) }}">
        {{ 'Edit' |trans }}
    </a>
    <h3>Comité #{{ item.id  }} {{ item.name }}</h3>

    <ul>
        {% for member in item.members %}
            <li>
                <a href="">{{ member.user.getCompleteName }}</a>
                <a class="btn" href="{{ url('jury.controller:removeMemberAction', {id : member.id}) }} ">Delete</a>
            </li>
        {% endfor %}
    </ul>

{% endblock %}
