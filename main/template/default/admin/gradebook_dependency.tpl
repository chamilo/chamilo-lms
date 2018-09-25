{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
    <h3>
        {{ gradebook_category.name }}
        {% if gradebook_category.courseCode is not empty %}
            ({{ gradebook_category.courseCode }})
        {% endif %}
    </h3>
    {{ 'MinimumGradebookToValidate' | get_lang }} :  {{ min_to_validate }}
    <br />
    {{ 'MandatoryCourses' | get_lang }}
    {% for course in mandatory_courses %}
        <th>
            {{ course.title }} ({{ course.code }})
        </th>
    {% endfor %}
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
            <th>{{ 'RestCoursesSubscribedResults' | get_lang }}</th>
            <th>{{ 'Progress' | get_lang }} Max 20 (Mandatory courses)</th>
            <th>{{ 'Progress' | get_lang }} Max 80 (Rest of courses)</th>
            <th>{{ 'Total' | get_lang }}</th>
        </tr>
    </thead>
    {% for user in users %}
        <tr>
            <td>{{ user.user_info.complete_name }}</td>

            {% for course in courses %}
            <td>
                {% if user.result_dependencies[course.code] %}
                    <img src="{{ 'check-circle.png'|icon(22) }}" />
                {% endif %}
            </td>
            {% endfor %}

            <td>
                {{ user.course_list_passed_out_dependency }} /
                {{ user.course_list_passed_out_dependency_count }}
            </td>

            <td>
                {{ user.result_mandatory_20 }}
            </td>
            <td>
                {{ user.result_not_mandatory_80 }}
            </td>

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
