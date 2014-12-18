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
                    <td><img src="{{ row.courseImage }}" alt="{{ row.courseName }}" width="64"> {{ row.courseName }}</td>
                </tr>
            {% endfor %}
        </tbody>
    </table>
{% else %}
    <div class="alert alert-info">
        {{ 'NoResults' | get_lang }}
    </div>
{% endif %}
