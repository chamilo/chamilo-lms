{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <a href="{{ url(links.list_link) }}">
        List
    </a>

    <h3>#{{ item.id }} {{ item.branchName }} </h3>

    {% if item.getUsers %}
        <hr />
        <h4> {{ 'Users' | trans }} </h4>

        <a class="btn" href="{{ url('branch.controller:addDirectorAction', { "id" : item.id }) }}"> Add users </a>
        {% for branchUsers in item.getUsers %}
            <li>
                {{ branchUsers.user.getCompleteName }} - {{ branchUsers.role.name }}
                <a class="btn btn-danger" href="{{ url('branch.controller:removeDirectorAction', { 'id': item.id, 'userId':branchUsers.user.userId }) }}">
                    Remove
                </a>
            </li>
        {% endfor %}
    {% endif %}
    <hr />

    <h4>{{ 'SubBranches' | trans }}</h4>
    <ul>
    {% for subitem in subitems %}
        <li>
            <a href="{{ url(links.read_link, { id: subitem.id} ) }}">
                {{ subitem.branchName }}
            </a>
        </li>
    {% endfor %}
    </ul>
{% endblock %}
