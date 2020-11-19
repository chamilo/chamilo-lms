{% if not courses is empty %}
    <div class="classic-courses">
    {% for item in courses %}
        <div class="panel panel-default">
            <div class="panel-body">
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
                        <div class="pull-right">
                            {{ item.unregister_button }}
                            {% if item.edit_actions != '' %}
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
                            {% endif %}
                        </div>
                        <h4 class="course-items-title">
                            {% if item.visibility == constant('COURSE_VISIBILITY_CLOSED') and not item.current_user_is_teacher %}
                                {{ item.title }} {{ item.code_course }} {{ item.url_marker }}
                            {% else %}
                                <a href="{{ item.link }}">
                                    {{ item.title }} {{ item.code_course }}
                                </a>
                                {{ item.url_marker }}
                                {{ item.notifications }}
                                {% if item.is_special_course %}
                                    {{ 'klipper.png' | img(22, 'CourseAutoRegister'|get_lang ) }}
                                {% endif %}
                            {% endif %}
                        </h4>
                        <div class="course-items-session">
                            <div class="list-teachers">
                                {% if item.teachers|length > 0 %}
                                    {{ 'teacher.png' | img(16, 'Professor'|get_lang ) }}
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
                        <div class="category">
                            {{ item.category }}
                        </div>
                        <div class="course_extrafields">
                            {% if item.extrafields|length > 0 %}
                            {% for extrafield in item.extrafields %}
                            {% set counter = counter + 1 %}
                            {% if counter > 1 %} | {% endif %}
                            {{ extrafield.text }} : <strong>{{ extrafield.value }}</strong>
                            {% endfor %}
                            {% endif %}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {% endfor %}
    </div>
{% endif %}