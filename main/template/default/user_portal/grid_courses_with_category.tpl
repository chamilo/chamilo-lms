<div class="grid-courses">
    {% for category in courses.in_category %}
        {% set nameCategory = category.title_category %}
        {% set idCategory = category.id_category %}
        <div id="category_{{ idCategory }}" class="panel panel-default">
            <div class="panel-heading">
                {{ nameCategory }}
            </div>
            <div class="panel-body">
                <div class="row">
                    {% for item in category.courses %}
                        <div class="col-xs-12 col-sm-6 col-md-4">
                            <div class="items">
                                <div class="image">
                                    <img src="{{ item.image }}" class="img-responsive">
                                    {% if item.category != '' %}
                                        <span class="category">{{ item.category }}</span>
                                        <div class="cribbon"></div>
                                    {% endif %}
                                    <div class="black-shadow">
                                        <div class="author-card">
                                            {% for teacher in item.teachers %}
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
                                    </div>
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
                                    <h4 class="title">
                                        {% if item.visibility == constant('COURSE_VISIBILITY_CLOSED') and not item.current_user_is_teacher %}
                                            {{ item.title }} {{ item.code_course }}
                                        {% else %}
                                            <a href="{{ item.link }}">{{ item.title }} {{ item.code_course }}</a>
                                        {% endif %}
                                    </h4>
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
        </div>
    {% endfor %}
</div>
