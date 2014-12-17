{% extends "default/layout/main.tpl" %}

{% block body %}
    <div class="span12">
        <div class="actions">
            <a href="{{ _p.web_main }}auth/my_progress.php"> 
                <img src="{{ _p.web_img }}icons/32/stats.png" alt="Ver mis estadísticas" title="Ver mis estadísticas">
            </a>
            <a href="{{ _p.web_main }}mySpace/student.php"> 
                <img src="{{ _p.web_img }}icons/32/user.png" alt="Estudiantes" title="Estudiantes">
            </a>
            <a href="{{ _p.web_main }}mySpace/teachers.php">
                <img src="{{ _p.web_img }}icons/32/teacher.png" alt="Profesores" title="Profesores">
            </a>
            <a href="{{ _p.web_main }}mySpace/course.php"> 
                <img src="{{ _p.web_img }}icons/32/course.png" alt="Cursos" title="Cursos">
            </a>
            <a href="{{ _p.web_main }}mySpace/session.php">
                <img src="{{ _p.web_img }}icons/32/session.png" alt="Sesiones de formación" title="Sesiones de formación">
            </a>
            <a href="{{ _p.web_main }}mySpace/skills.php">
                <img src="{{ _p.web_img }}icons/32/skills.png" alt="Competencias" title="Competencias">
            </a>
        </div>

        <h1 class="page-header">{{ 'Skills' | get_lang }}</h1>

        <div class="row">
            <div class="span6">
                <form class="form-inline" method="post" action="{{ _p.web_self }}">
                    <label for="course">{{ 'Courses' | get_lang }}</label>
                    <select name="course" id="course">
                        <option value="0">{{ 'Select' | get_lang }}</option>
                        {% for course in courses %}
                            <option value="{{ course.id }}" {{ (course.id == selectedCourse) ? 'selected' : '' }}>{{ course.title }}</option>
                        {% endfor %}
                    </select>
                    <button type="submit" class="btn">{{ 'Filter' | get_lang }}</button>
                </form>
            </div>
            <div class="span6">
                <form class="form-inline" method="post" action="{{ _p.web_self }}">
                    <label for="skill">{{ 'Skills' | get_lang }}</label>
                    <select name="skill" id="skill">
                        <option value="0">{{ 'Select' | get_lang }}</option>
                        {% for skill in skills %}
                            <option value="{{ skill.id }}" {{ (skill.id == selectedSkill) ? 'selected' : '' }}>{{ skill.name }}</option>
                        {% endfor %}
                    </select>
                    <button type="submit" class="btn">{{ 'Filter' | get_lang }}</button>
                </form>
            </div>
        </div>

        <h2 class="page-header">{{ reportTitle }} <small>{{ reportSubTitle }}</small></h2>

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
                                <td><img src="{{ row.courseImage }}" alt="{{ row.c_name }}" width="64"> {{ row.c_name }}</td>
                                <td>{{ row.skill_name }}</td>
                                <td>{{ row.completeName }}</td>
                                <td>{{ row.achievedAt }}</td>
                            {% elseif action == 'filterBySkill' %}
                                <td>{{ row.skill_name }}</td>
                                <td>{{ row.completeName }}</td>
                                <td>{{ row.achievedAt }}</td>
                                <td><img src="{{ row.courseImage }}" alt="{{ row.c_name }}" width="64"> {{ row.c_name }}</td>
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
    </div>
{% endblock %}