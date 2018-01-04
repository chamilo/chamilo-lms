<!--
{% if allow_skill_tool %}
    <div class="btn-group">
        <a class="btn btn-default" href="{{ _p.web_main }}social/skills_wheel.php">
            {{ 'SkillsWheel' | get_lang }}
        </a>
    </div>
{% endif %}
-->
<style>


</style>
<h1 class="page-header">{{ 'MyBadges' | get_lang }}</h1>
<p>
{{ 'MySkillsReportIntroduction' | get_lang }}
</p>
{% if rows %}
    {% if skill_table %}
        {{ skill_table }}
    {% endif %}

    <h6 class="table-header">{{ 'SkillsHistory' | get_lang }}</h6>
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
                <td>{{ row.skill_badge }}</td>
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
{% endif %}
