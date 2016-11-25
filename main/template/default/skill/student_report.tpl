{% if allowSkillsTool %}
    <div class="btn-group">
        <a class="btn btn-default" href="{{ _p.web_main }}social/skills_wheel.php">{{ 'SkillsWheel' | get_lang }}</a>
    </div>
{% endif %}

<h1 class="page-header">{{ 'SkillsAcquired' | get_lang }}</h1>

{% if rows %}
    <table class="table">
        <thead>
            <tr>
                <th>{{ 'Skill' | get_lang }}</th>
                <th>{{ 'Date' | get_lang }}</th>
                <th>{{ 'Course' | get_lang }}</th>
            </tr>
        </thead>
        <tbody>
            {% for row in rows %}
                <tr>
                    <td>{{ row.skillName }}</td>
                    <td>{{ row.achievedAt }}</td>
                    {% if row.courseName %}
                        <td><img src="{{ row.courseImage }}" alt="{{ row.courseName }}" width="32"> {{ row.courseName }}</td>
                    {% else %}
                        <td> - </td>
                    {% endif %}
                </tr>
            {% endfor %}
        </tbody>
    </table>
{% else %}
    <div class="alert alert-info">
        {{ 'NoResults' | get_lang }}
    </div>
{% endif %}
