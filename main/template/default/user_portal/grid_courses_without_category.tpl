{% if not courses is empty %}
    <div class="grid-courses">
        <div class="row">
            {% for item in courses %}
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <div class="items my-courses">
                        <div class="image">
                            {% if item.is_special_course %}
                                <div class="pin">{{ item.icon }}</div>
                            {% endif %}
                            {% if item.visibility == constant('COURSE_VISIBILITY_CLOSED') and not item.current_user_is_teacher %}
                                <img src="{{ item.image }}" class="img-responsive">
                            {% else %}
                                <a title="{{ item.title }}" href="{{ item.link }}">
                                    <img src="{{ item.image }}" alt="{{ item.title }}" class="img-responsive">
                                </a>
                            {% endif %}
                            {% if item.category != '' %}
                                <span class="category">{{ item.category }}</span>
                                <div class="cribbon"></div>
                            {% endif %}
                            
                            {% if item.edit_actions != '' %}
                                <div class="admin-actions">
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
                        </div>
                        <div class="description">
                            <div class="block-title">
                                <h4 class="title" title="{{ item.title }}">
                                    {% if item.visibility == constant('COURSE_VISIBILITY_CLOSED') and not item.current_user_is_teacher %}
                                        {{ item.title_cut }} {{ item.code_course }}
                                    {% else %}
                                        <a title="{{ item.title }}" href="{{ item.link }}">{{ item.title_cut }} {{ item.code_course }}</a>
                                    {% endif %}
                                </h4>
                            </div>
                            <div class="block-author">
                                {% for teacher in item.teachers %}
                                    {% if item.teachers | length > 2 %}
                                        <a href="{{ teacher.url }}" class="ajax"
                                           data-title="{{ teacher.firstname }} {{ teacher.lastname }}">
                                            <img src="{{ teacher.avatar }}"/>
                                        </a>
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
                                            <p>{{ 'Teacher' | get_lang }}</p>
                                        </div>
                                    {% endif %}
                                {% endfor %}
                            </div>
                            <div class="notifications">{{ item.notifications }}</div>

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
                                                <span title="{{ "StudentCourseCertificateX" | get_lang | format(item.student_info.certificate) }}">
                                                    <i class="fa fa-certificate" aria-hidden="true"></i>
                                                    {{ item.student_info.certificate }}
                                                </span>
                                            {% endif %}

                                        </div>
                                    </div>
                                {% endif %}
                            {% endif %}

                        </div>
                    </div>
                </div>
            {% endfor %}
        </div>
    </div>
{% endif %}
