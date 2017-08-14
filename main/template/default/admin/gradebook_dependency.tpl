{% extends template ~ "/layout/layout_1_col.tpl" %}

{% block content %}
    {% for course in courses %}
        <h3>
            {{ course.title }} ({{ course.code }})
        </h3>

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
