{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
    <a href="{{ url(links.create_link, {'course' : course.code, 'id_session': course_session.id }) }}">
        {{ 'Add' |trans }}
    </a>
    <table class="table">
    {% for item in items %}
        <tr>
            <td>
                <a href="{{ url(links.read_link, {'course' : course.code, 'id_session': course_session.id, id: item.id }) }}">
                {{ item.title }}
                </a>
            </td>
            <td>
                <a class="btn" href="{{ url(links.update_link, {'course' : course.code, 'id_session': course_session.id, id: item.id }) }}"> {{ 'Edit' | trans }}</a>
                <a class="btn" href="{{ url(links.delete_link, {'course' : course.code, 'id_session': course_session.id, id: item.id }) }}"> {{ 'Delete' |trans }}</a>
            </td>
        </tr>
    {% endfor %}
    </table>
{% endblock %}
