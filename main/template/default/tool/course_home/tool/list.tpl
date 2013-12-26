{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}

{% block content %}
    <table class="table">
        {% for item in items %}
            <tr>
                <td>
                    <a href="{{ url(links.read_link, {'course' : course.code, 'id_session': course_session.id, id: item.id }) }}">
                        {% if item.customIcon %}
                            <img src="{{ url('getCourseUploadFileAction', { courseCode:course.code, file: 'course_home_icons/'~item.customIcon}) }}"/>
                        {% else %}
                            <img src="{{ _p.web_img ~ 'icons/64/' ~ item.imageGifToPng }}"/>
                        {% endif %}
                    </a>
                </td>
                <td>
                    <a href="{{ url(links.read_link, {'course' : course.code, 'id_session': course_session.id, id: item.id }) }}">
                        {{ item.name }}
                    </a>
                </td>
                <td>
                    <a class="btn" href="{{ url(links.update_link, {'course' : course.code, 'id_session': course_session.id, id: item.id }) }}">
                        {{ 'Edit' | trans }}
                    </a>
                    {% if item.customIcon %}
                    <a class="btn" href="{{ url('course_home.controller:deleteIconAction', {'course' : course.code, 'id_session': course_session.id, itemId: item.id }) }}">
                        {{ 'Delete' |trans }}
                    </a>
                    {% endif %}
                </td>
            </tr>
        {% endfor %}
    </table>
{% endblock %}