{% if allow_skill_tool %}
    <div class="btn-group">
        <a class="btn btn-default" href="{{ _p.web_main }}social/skills_wheel.php">
            {{ 'SkillsWheel' | get_lang }}
        </a>
    </div>
{% endif %}

<h1 class="page-header">{{ 'SkillsAcquired' | get_lang }}</h1>

<form class="form-inline" method="post" action="{{ _p.web_self }}">
    <label>{{ 'Students' | get_lang }}</label>
    <select name="student" id="student">
        <option value="0">{{ 'Select' | get_lang }}</option>
        {% for student in followed_students %}
            <option value="{{ student.user_id }}" {{ (student.user_id == selected_student) ? 'selected' : '' }}>
                {{ student.completeName }}
            </option>
        {% endfor %}
    </select>
    <button type="submit" class="btn btn-primary">{{ 'Search' | get_lang }}</button>
</form>

{% if rows %}
    <table class="table">
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
                <td><img src="{{ row.course_image }}" alt="{{ row.course_name }}"> {{ row.course_name }}</td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
{% endif %}
