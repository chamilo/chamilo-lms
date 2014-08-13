{% extends "@template_style/layout/layout_1_col.tpl" %}
{% block content %}
    <a href="{{ url(links.list_link) }}">
        {{ 'List' |trans }}
    </a>

    <a href="{{ url('session_tree.controller:addTreeItemAction') }}">
        {{ 'Add' |trans }}
    </a>

     {{ item.id }}
    <hr />
    <ul>
    {% for subitem in subitems %}
        <li>
            <a href="{{ url(links.read_link, { id: subitem.id} ) }}">{{ subitem.branchName }}</a>
        </li>
    {% endfor %}
    </ul>
{% endblock %}
