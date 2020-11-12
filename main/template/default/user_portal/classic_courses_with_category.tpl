{% import 'default/macro/macro.tpl' as display %}

{% if not categories is empty %}
    <div class="classic-courses">
        {% for category in categories %}
            {% if category.courses %}
                {% set course_content %}
                {% for item in category.courses %}
                    <div class="row">
                        <div class="col-md-2">
                            {% if item.visibility == constant('COURSE_VISIBILITY_CLOSED') and not item.current_user_is_teacher %}
                                <span class="thumbnail">
                                        {% if item.thumbnails != '' %}
                                            <img src="{{ item.thumbnails }}" title="{{ item.title }}"
                                                 alt="{{ item.title }}"/>
                                        {% else %}
                                            {{ 'blackboard.png' | img(48, item.title ) }}
                                        {% endif %}
                                    </span>
                            {% else %}
                                <a href="{{ item.link }}" class="thumbnail">
                                    {% if item.thumbnails != '' %}
                                        <img src="{{ item.thumbnails }}" title="{{ item.title }}"
                                             alt="{{ item.title }}"/>
                                    {% else %}
                                        {{ 'blackboard.png' | img(48, item.title ) }}
                                    {% endif %}
                                </a>
                            {% endif %}
                        </div>
                        <div class="col-md-10">
                            {% if item.edit_actions != '' %}
                                <div class="pull-right">
                                    {% if item.document == '' %}
                                        <a class="btn btn-default btn-sm" href="{{ item.edit_actions }}">
                                            <i class="fa fa-pencil" aria-hidden="true"></i>
                                        </a>
                                    {% else %}
                                        <div class="btn-group" role="group">
                                            <a class="btn btn-default btn-sm" href="{{ item.edit_actions }}">
                                                <i class="fa fa-pencil" aria-hidden="true"></i>
                                            </a>
                                            {{ item.document }}
                                        </div>
                                    {% endif %}
                                </div>
                            {% endif %}
                            <h4 class="course-items-title">
                                {% if item.visibility == constant('COURSE_VISIBILITY_CLOSED') and not item.current_user_is_teacher %}
                                    {{ item.title }} {{ item.code_course }} {{ item.url_marker }}
                                {% else %}
                                    <a href="{{ item.link }}">
                                        {{ item.title }} {{ item.code_course }} {{ item.url_marker }}
                                    </a>
                                    {{ item.notifications }}
                                {% endif %}
                            </h4>
                            <div class="course-items-session">
                                <div class="list-teachers">
                                    {% if item.teachers|length > 0 %}
                                        <img src="{{ 'teacher.png'|icon(16) }}" width="16" height="16">&nbsp;
                                        {% for teacher in item.teachers %}
                                            {% set counter = counter + 1 %}
                                            {% if counter > 1 %} | {% endif %}
                                            <a href="{{ teacher.url }}" class="ajax"
                                               data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                                {{ teacher.firstname }} {{ teacher.lastname }}
                                            </a>
                                        {% endfor %}
                                    {% endif %}
                                </div>
                                {% if item.student_info %}
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
                                {% endif %}
                            </div>
                        </div>
                    </div>
                {% endfor %}
                {% endset %}

                {{ display.collapse(
                    'course_category_' ~ category.id_category,
                    category.title_category ~ '<div class="pull-right">'~ category.collapsable_link~ "</div>",
                    course_content,
                    false,
                    category.collapsed == 0
                )
                }}
            {% endif %}
        {% endfor %}
    </div>
{% endif %}

