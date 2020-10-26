{% set group_courses = 'view_grid_courses_grouped_categories_in_sessions'| api_get_configuration_value %}

{% macro course_block(course, show_category) %}
    <div class="col-xs-12 col-sm-6 col-md-4">
        <div class="items items-sessions">
            <div class="image">
                {% if course.visibility == constant('COURSE_VISIBILITY_CLOSED') or course.requirements %}
                    <span title="{{ course.name }}" >
                        <img src="{{ course.image }}" class="img-responsive">
                    </span>
                {% else %}
                    <a title="{{ course.name }}" href="{{ course.link }}">
                        <img src="{{ course.image }}" class="img-responsive">
                    </a>
                {% endif %}

                {% if course.category != '' and show_category %}
                    <span class="category">{{ course.category }}</span>
                    <div class="cribbon"></div>
                {% endif %}
                {% if course.edit_actions %}
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
                <div class="block-title">
                  <h4 class="title">
                      {% if course.visibility == constant('COURSE_VISIBILITY_CLOSED') or course.requirements %}
                          {{ course.name }}
                          <span class="code-title">{{ course.visual_code }}</span>
                      {% else %}
                          {{ course.title }}
                      {% endif %}
                  </h4>
                </div>
                <div class="block-author">
                    {{ course.requirements }}
                    {% if course.coaches | length > 2 %}
                        <a
                            id="plist-{{ course.real_id }}"
                            data-trigger="focus"
                            tabindex="0" role="button"
                            class="btn btn-default panel_popover"
                            data-toggle="popover"
                            title="{{ 'CourseTeachers' | get_lang }}"
                            data-html="true"
                        >
                            <i class="fa fa-graduation-cap" aria-hidden="true"></i>
                        </a>
                        <div id="popover-content-plist-{{ course.real_id }}" class="hide">
                    {% endif %}

                    {% for teacher in course.coaches %}
                        {% if course.coaches | length > 2 %}
                              <div class="popover-teacher">
                              <a href="{{ teacher.url }}" class="ajax">
                                  <img src="{{ teacher.avatar }}"/>
                              </a>
                                  <div class="teachers-details">
                                      <h5>
                                      {{ teacher.firstname }} {{ teacher.lastname }}
                                      </h5>
                                  </div>
                              </div>
                        {% else %}
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
                              <p>{{ "Teacher"|get_lang }}</p>
                          </div>
                        {% endif %}
                    {% endfor %}

                    {% if course.coaches | length > 2 %}
                        </div>
                    {% endif %}
                </div>
                <div class="notifications">
                    {{ course.notifications }}
                </div>
                {% if item.student_info %}
                    <div class="black-student">
                        {% if item.student_info.progress is not null or item.student_info.score is not null or item.student_info.certificate is not null %}
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
                  </div>
              {% endif %}
            </div>
        </div>
    </div>
{% endmacro %}

{% import _self as blocks %}

{% set session_image = 'window_list.png'|img(32, row.title) %}

{% for row in session %}
    {% set collapsable = '' %}
    <div id="session-{{ item.id }}" class="session panel panel-default">
        {% if row.course_list_session_style %}
            {# If not style then no show header #}
            <div class="panel-heading">
                {% if row.course_list_session_style == 1 or row.course_list_session_style == 2 %}
                    {# Session link #}
                    {% if remove_session_url == true %}
                        {{ session_image }} {{ row.title }}
                    {% else %}
                        {# Default link #}
                        {% set session_link = _p.web_main ~ 'session/index.php?session_id=' ~ row.id %}
                        {% if row.course_list_session_style == 2 and row.courses|length == 1 %}
                            {# Linkt to first course #}
                            {% set session_link = row.courses.0.link %}
                        {% endif %}
                        <a href="{{ session_link }}">
                            {{ session_image }} {{ row.title }}
                        </a>
                    {% endif %}
                {% elseif row.course_list_session_style == 4 %}
                    {{ session_image }} {{ row.title }}
                {% elseif row.course_list_session_style == 3 %}
                    {# Collapsible panel #}
                    {# Foldable #}
                    <a role="button" data-toggle="collapse" data-parent="#page-content" href="#collapse_{{ row.id }}"
                       aria-expanded="false">
                        {{ session_image }} {{ row.title }}
                    </a>
                    {% set collapsable = 'collapse' %}
                {% endif %}
                {% if row.show_actions %}
                    <div class="pull-right">
                        <a href="{{ _p.web_main ~ "session/resume_session.php?id_session=" ~ row.id }}">
                            <img src="{{ "edit.png"|icon(22) }}" width="22" height="22" alt="{{ "Edit"|get_lang }}"
                                 title="{{ "Edit"|get_lang }}">
                        </a>
                    </div>
                {% endif %}
                {% if row.collapsable_link %}
                    <div class="pull-right">
                       {{ row.collapsable_link }}
                    </div>
                {% endif %}
            </div>
        {% endif %}

        {% if row.collapsable_link %}
            {% if row.collapsed == 1 %}
                {% set collapsable = 'collapse' %}
            {% endif %}
        {% endif %}

        <div class="session panel-body {{ collapsable }}" id="collapse_{{ row.id }}">
            {% if row.show_description %}
                {{ row.description }}
            {% endif %}
            <ul class="info-session list-inline">
                {% if row.coach_name %}
                    <li>
                        <i class="fa fa-user" aria-hidden="true"></i>
                        {{ row.coach_name }}
                    </li>
                {% endif %}

                {% if hide_session_dates_in_user_portal == false %}
                    {% if row.date %}
                        <li>
                            <i class="fa fa-calendar" aria-hidden="true"></i> {{ row.date }}
                        </li>
                    {% elseif row.duration %}
                        <li>
                            <i class="fa fa-calendar" aria-hidden="true"></i> {{ row.duration }}
                        </li>
                    {% endif %}
                {% endif %}
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
                        <h4>{{ category_code }}</h4>
                        <div class="row">
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
