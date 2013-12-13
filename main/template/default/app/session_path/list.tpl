{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <a href="{{ url(links.create_link) }}">
        {{ 'Add' |trans }}
    </a>
    <table class="table">
        {% for item in items %}
            <tr>
                <td>
                    <a href="{{ url(links.read_link, { id: item.id }) }}">
                        {{ item.name }}
                    </a>
                </td>
                <td>
                    <a class="btn" href="{{ url(links.update_link, { id: item.id }) }}"> {{ 'Edit' |trans }}</a>
                    <a class="btn" href="{{ url(links.delete_link, { id: item.id }) }}"> {{ 'Delete' |trans }}</a>
                </td>
            </tr>
        {% endfor %}
    </table>
{% endblock %}
