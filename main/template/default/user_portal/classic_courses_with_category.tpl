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
                                        {% set one_column = item.student_info.score is null and item.student_info.certificate is null %}
                                        <div class="course-student-info">
                                            <div class="student-info">
                                                <div class="row">
                                                    <div class="{{ one_column ? 'col-xs-12' : 'col-xs-8' }}">
                                                        {{ 'CourseProgress'|get_lang }}
                                                        <div class="progress">
                                                            <div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="{{ item.student_info.progress }}"
                                                                    aria-valuemin="0" aria-valuemax="100" style="width: {{ item.student_info.progress }}%;">
                                                                {{ 'XPercent'|get_lang|format(item.student_info.progress) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="{{ one_column ? '' : 'col-xs-4' }}">
                                                        {% if item.student_info.score is not null %}
                                                            <div>{{ "StudentCourseScoreX" | get_lang | format(item.student_info.score) }}</div>
                                                        {% endif %}
                                                        {% if item.student_info.certificate is not null %}
                                                            <div title="{{ "StudentCourseCertificateX" | get_lang | format(item.student_info.certificate) }}">
                                                                <i class="fa fa-certificate text-warning" aria-hidden="true"></i>
                                                                {{ item.student_info.certificate }}
                                                            </div>
                                                        {% endif %}
                                                    </div>
                                                </div>
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

