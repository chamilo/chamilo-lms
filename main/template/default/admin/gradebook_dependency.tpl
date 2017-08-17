{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
    <h3>
        {{ gradebook_category.name }}
        {% if gradebook_category.courseCode is not empty %}
            ({{ gradebook_category.courseCode }})
        {% endif %}
    </h3>
    <hr>
    <table class="table">
     <thead class="title">
        <tr>
            <th>{{ 'Users' | get_lang }}</th>
            {% for course in courses %}
                <th>
                    {{ course.title }} ({{ course.code }})
                </th>
            {% endfor %}
            <th>{{ 'Total' | get_lang }}</th>
        </tr>
    </thead>
    {% for user in users %}
        <tr>
            <td>{{ user.user_info.complete_name }}</td>

            {% for course in courses %}
            <td>
                {% if user.result[course.code] %}
                    <img src="{{ 'check-circle.png'|icon(22) }}" />
                {% endif %}
            </td>
            {% endfor %}

            <td>
                {% if user.final_result %}
                    <img src="{{ 'check-circle.png'|icon(22) }}" />
                {% else %}
                    <img src="{{ 'warning.png'|icon(22) }}" />
                {% endif %}
            </td>
        </tr>
    {% endfor %}
    </table>
{% endblock %}
