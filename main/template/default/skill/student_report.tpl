{% if allow_skill_tool %}
    <div class="btn-group">
        <a class="btn btn-default" href="{{ _p.web_main }}social/skills_wheel.php">
            {{ 'SkillsWheel' | get_lang }}
        </a>

        <a class="btn btn-default" href="{{ _p.web_main }}social/my_skills_report.php?a=generate">
            {{ 'RecalculateResults' | get_lang }}
        </a>
    </div>
{% endif %}

{% if rows %}
    <h1 class="page-header">{{ 'SkillsAcquired' | get_lang }}</h1>
    <table class="table">
        <thead>
            <tr>
                <th>{{ 'Badge' | get_lang }}</th>
                <th>{{ 'Skill' | get_lang }}</th>
                <th>{{ 'Date' | get_lang }}</th>
                <th>{{ 'Course' | get_lang }}</th>
            </tr>
        </thead>
        <tbody>
        {% for row in rows %}
            <tr>
                <td>
                    <a href="{{ row.skill_url }}">
                        {{ row.skill_badge }}
                    </a>
                </td>
                <td>{{ row.skill_name }}</td>
                <td>{{ row.achieved_at }}</td>
                {% if row.course_name %}
                    <td>
                        <img src="{{ row.course_image }}" alt="{{ row.course_name }}" width="32">
                        {{ row.course_name }}
                    </td>
                {% else %}
                    <td> - </td>
                {% endif %}
            </tr>
        {% endfor %}
        </tbody>
    </table>
    {% if skill_table %}
        {{ skill_table }}
    {% endif %}
{% endif %}
