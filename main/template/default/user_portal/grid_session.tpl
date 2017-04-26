{% set group_courses = 'view_grid_courses_grouped_categories_in_sessions'| api_get_configuration_value %}

{% macro course_block(course, show_category) %}
    <div class="col-xs-12 col-sm-6 col-md-4">
        <div class="items">
            <div class="image">
                <img src="{{ course.image }}" class="img-responsive">
                {% if course.category != '' and show_category %}
                    <span class="category">{{ course.category }}</span>
                    <div class="cribbon"></div>
                {% endif %}
                <div class="black-shadow">
                    <div class="author-card">
                        {% for teacher in course.teachers %}
                            {% set counter = counter + 1 %}
                            {% if counter <= 3 %}
                                <a href="{{ teacher.url }}" class="ajax"
                                   data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                    <img src="{{ teacher.avatar }}"/>
                                </a>
                                <div class="teachers-details">
                                    <h5>
                                        <a href="{{ teacher.url }}" class="ajax"
                                           data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                            {{ teacher.firstname }} {{ teacher.lastname }}
                                        </a>
                                    </h5>
                                </div>
                            {% endif %}
                        {% endfor %}
                    </div>
                    {% if item.student_info %}
                        {% if (item.student_info.progress is not null) and (item.student_info.score is not null) %}
                            <div class="course-student-info">
                                <div class="student-info">

                                    {% if (item.student_info.progress is not null) %}
                                    {{ "StudentCourseProgressX" | get_lang | format(item.student_info.progress) }}
                                    {% endif %}

                                    {% if (item.student_info.score is not null) %}
                                    {{ "StudentCourseScoreX" | get_lang | format(item.student_info.score) }}
                                    {% endif %}

                                    {% if (item.student_info.certificate is not null) %}
                                    {{ "StudentCourseCertificateX" | get_lang | format(item.student_info.certificate) }}
                                    {% endif %}

                                </div>
                            </div>
                        {% endif %}
                    {% endif %}

                </div>
                {% if course.edit_actions != '' %}
                    <div class="admin-actions">
                        {% if course.document == '' %}
                            <a class="btn btn-default btn-sm" href="{{ course.edit_actions }}">
                                <i class="fa fa-pencil" aria-hidden="true"></i>
                            </a>
                        {% else %}
                            <div class="btn-group" role="group">
                                <a class="btn btn-default btn-sm" href="{{ course.edit_actions }}">
                                    <i class="fa fa-pencil" aria-hidden="true"></i>
                                </a>
                                {{ course.document }}
                            </div>
                        {% endif %}
                    </div>
                {% endif %}
            </div>
            <div class="description">
                <h4 class="title">
                    {% if course.visibility == constant('COURSE_VISIBILITY_CLOSED') %}
                        {{ course.title }} {{ course.code_course }}
                    {% else %}
                        <a href="{{ course.link }}">{{ course.title }} {{ course.code_course }}</a>
                    {% endif %}
                </h4>
                <div class="notifications">{{ course.notifications }}</div>
            </div>
        </div>
    </div>
{% endmacro %}

{% import _self as blocks %}

{% for row in session %}
    <div id="session-{{ item.id }}" class="session panel panel-default">
        <div class="panel-heading">
            <img id="session_img_{{ row.id }}" src="{{ "window_list.png"|icon(32) }}" width="32" height="32"
                 alt="{{ row.title }}" title="{{ row.title }}"/>
            {{ row.title }}
            {% if row.edit_actions != '' %}
                <div class="pull-right">
                    <a class="btn btn-default btn-sm" href="{{ row.edit_actions }}">
                        <i class="fa fa-pencil" aria-hidden="true"></i>
                    </a>
                </div>
            {% endif %}
        </div>
        <div class="panel-body">
            {% if row.description != '' %}
                {{ row.description }}
            {% endif %}

            <ul class="info-session list-inline">
                {% if row.coach_name %}
                    <li>
                        <i class="fa fa-user" aria-hidden="true"></i>
                        {{ row.coach_name }}
                    </li>
                {% endif %}

                <li>
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                    {{ row.date ? row.date : row.duration }}
                </li>
            </ul>
            <div class="grid-courses">
                {% if not group_courses %}
                    <div class="row">
                        {% for item in row.courses %}
                            {{ blocks.course_block(item, true) }}
                        {% endfor %}
                    </div>
                {% else %}
                    {% for category_code in row.course_categories %}
                        <div class="row">
                            <div class="col-xs-12">
                                <h4>{{ category_code }}</h4>
                            </div>
                            {% for course in row.courses %}
                                {% if course.category == category_code %}
                                    {{ blocks.course_block(course, false) }}
                                {% endif %}
                            {% endfor %}
                        </div>
                    {% endfor %}
                {% endif %}
            </div>
        </div>
    </div>
{% endfor %}
