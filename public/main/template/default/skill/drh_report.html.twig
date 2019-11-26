{% if allow_skill_tool %}
    <div class="btn-group">
        <a class="btn btn-default" href="{{ _p.web_main }}social/skills_wheel.php">
            {{ 'SkillsWheel' | get_lang }}
        </a>
        {% if allow_drh_skills_management %}
            <a class="btn btn-default" href="{{ _p.web_main }}admin/skills_wheel.php">
                {{ 'ManageSkills' | get_lang }}
            </a>
        {% endif %}
    </div>
{% endif %}

<h1 class="page-header">{{ 'Skills' | get_lang }}</h1>
<div class="row">
    <div class="col-md-6">
        <form class="form-inline" method="post" action="{{ _p.web_self }}">
            <label for="course">{{ 'Courses' | get_lang }}</label>
            <select name="course" id="course" class="form-control">
                <option value="0">{{ 'Select' | get_lang }}</option>
                {% for course in courses %}
                    <option value="{{ course.id }}" {{ (course.id == selected_course) ? 'selected' : '' }}>{{ course.title }}</option>
                {% endfor %}
            </select>
            <button type="submit" class="btn btn-default">
                <span class="fa fa-filter" aria-hidden="true"></span>
                {{ 'Filter' | get_lang }}
            </button>
        </form>
    </div>
    <div class="col-md-6">
        <form class="form-inline" method="post" action="{{ _p.web_self }}">
            <label for="skill">{{ 'Skills' | get_lang }}</label>
            <select name="skill" id="skill" class="form-control">
                <option value="0">{{ 'Select' | get_lang }}</option>
                {% for skill in skills %}
                    <option value="{{ skill.id }}" {{ (skill.id == selected_skill) ? 'selected' : '' }}>
                        {{ skill.name }}
                    </option>
                {% endfor %}
            </select>
            <button type="submit" class="btn btn-default">
                <span class="fa fa-filter" aria-hidden="true"></span>
                {{ 'Filter' | get_lang }}
            </button>
        </form>
    </div>
</div>

<h2 class="page-header">{{ report_title }}</h2>
{% if rows %}
    <table class="table">
        <thead>
            <tr>
                {% if action == 'filterByCourse' %}
                    <th>{{ 'Course' | get_lang }}</th>
                    <th>{{ 'Skill' | get_lang }}</th>
                    <th>{{ 'Student' | get_lang }}</th>
                    <th>{{ 'Date' | get_lang }}</th>
                {% elseif action == 'filterBySkill' %}
                    <th>{{ 'Skill' | get_lang }}</th>
                    <th>{{ 'Student' | get_lang }}</th>
                    <th>{{ 'Date' | get_lang }}</th>
                    <th>{{ 'Course' | get_lang }}</th>
                {% endif %}
            </tr>
        </thead>
        <tbody>
        {% for row in rows %}
            <tr>
                {% if action == 'filterByCourse' %}
                    <td><img src="{{ row.courseImage }}" alt="{{ row.c_name }}"> {{ row.c_name }}</td>
                    <td>{{ row.skill_name }}</td>
                    <td>{{ row.complete_name }}</td>
                    <td>{{ row.achieved_at }}</td>
                {% elseif action == 'filterBySkill' %}
                    <td>{{ row.skill_name }}</td>
                    <td>{{ row.complete_name }}</td>
                    <td>{{ row.achieved_at }}</td>
                    <td><img src="{{ row.course_image }}" alt="{{ row.c_name }}"> {{ row.c_name }}</td>
                {% endif %}
            </tr>
        {% endfor %}
        </tbody>
    </table>
{% endif %}
