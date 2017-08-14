{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
    <h3>
    {{ gradebook_category.name }}
        {% if gradebook_category.courseCode is not empty %}
            ({{ gradebook_category.courseCode }})
        {% endif %}
    </h3>
    <hr>
    {% for course in courses %}
        <h4>
            {{ course.title }} ({{ course.code }})
        </h4>

        {% if course.users %}
            <table class="table">
             <thead class="title">
                <tr>
                    <th>{{ 'Users' | get_lang }}</th>
                    <th>{{ 'Result' | get_lang }}</th>
                </tr>
            </thead>
            {% for user in course.users %}
                <tr>
                    <td>{{ user.complete_name }}</td>
                    <td>
                        {% if user.result %}
                            <img src="{{ 'check-circle.png'|icon(22) }}" />
                        {% else %}
                        {% endif %}
                    </td>
                </tr>
            {% endfor %}
            </table>
        {% endif %}
    {% endfor %}
{% endblock %}
