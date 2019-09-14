{% if allow_skill_tool %}
    <div class="btn-group">
        <a class="btn btn-default" href="{{ _p.web_main }}social/skills_wheel.php">
            {{ 'SkillsWheel' | get_lang }}
        </a>
    </div>
{% endif %}

<h1 class="page-header">{{ 'SkillsAcquired' | get_lang }}</h1>

{{ form }}

{% if rows %}
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>{{ 'Student' | get_lang }}</th>
                <th>{{ 'SkillsAcquired' | get_lang }}</th>
                <th>{{ 'Date' | get_lang }}</th>
                <th>{{ 'Course' | get_lang }}</th>
            </tr>
            </thead>
            <tbody>
            {% for row in rows %}
                <tr>
                    <td>{{ row.complete_name }}</td>
                    <td>{{ row.skill_name }}</td>
                    <td>{{ row.achieved_at }}</td>
                    <td>
                        {% if not row.course_name is empty %}
                            <img src="{{ row.course_image }}" alt="{{ row.course_name }}"> {{ row.course_name }}
                        {% endif %}
                    </td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
    </div>
{% endif %}
