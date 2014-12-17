{% extends "default/layout/main.tpl" %}

{% block body %}
    <div class="span12">
        <div class="actions">
            <a href="{{ _p.web_main }}auth/my_progress.php"> 
                <img src="{{ _p.web_img }}icons/32/stats.png" alt="{{ 'MyStats' | get_lang }}" title="{{ 'MyStats' | get_lang }}">
            </a>
            <a href="{{ _p.web_main }}mySpace/student.php"> 
                <img src="{{ _p.web_img }}icons/32/user.png" alt="{{ 'Students' | get_lang }}" title="{{ 'Students' | get_lang }}">
            </a>
            <a href="#">
                <img src="{{ _p.web_img }}icons/32/skills.png" alt="Competencias" title="Competencias">
            </a>
        </div>

        <h1 class="page-header">{{ 'SkillsAcquired' | get_lang }}</h1>

        <form class="form-inline" method="post" action="{{ _p.web_self }}">
            <label>{{ 'Students' | get_lang }}</label>
            <select name="student" id="student">
                <option value="0">{{ 'Select' | get_lang }}</option>
                {% for student in followedStudents %}
                    <option value="{{ student.user_id }}" {{ (student.user_id == selectedStudent) ? 'selected' : '' }}>{{ student.completeName }}</option>
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
                            <td>{{ row.completeName }}</td>
                            <td>{{ row.name }}</td>
                            <td>{{ row.achievedAt }}</td>
                            <td><img src="{{ row.courseImage }}" alt="{{ row.c_name }}" width="64"> {{ row.c_name }}</td>
                        </tr>
                    {% endfor %}
                </tbody>
            </table>
        {% else %}
            <div class="alert alert-info">
                {{ 'NoResults' | get_lang }}
            </div>
        {% endif %}
    </div>
{% endblock %}