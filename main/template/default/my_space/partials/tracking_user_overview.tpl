<div class="summary-height">
    <div class="summary">
        <div class="summary-body">
            <div id="summary-user-{{ item.id }}" class="summary-item">
                <div class="icon">
                    <img src="{{ item.avatar }}" class="img-circle">
                </div>
                <div class="user">
                    <a title="{{ item.complete_name }}" href="{{ _p.web }}main/social/profile.php?u={{ item.id }}" class="name">
                        {{ item.complete_name }}
                    </a>
                    <div class="username">{{ item.username }}</div>
                </div>
                <div class="summary-course">
                    {% if item.course %}
                    {% for course in item.course %}
                        <div id="course-{{ course.real_id }}" class="course-item">
                            <div class="course-info">
                                <h5><a title="{{ 'Course'|get_lang }} - {{ course.title }}" href="{{ _p.web ~ 'main/mySpace/myStudents.php?details=true' ~ _p.web_cid_query ~ '&course=' ~ course.code ~ '&origin=tracking_course&id_session=0&student=' ~ item.id }}" target="_blank">{{ course.title }}</a></h5>
                                <span class="code">{{ course.code }}</span>
                            </div>
                            <div class="box time-spent" data-toggle="tooltip" data-placement="top" title="{{ 'CourseTimeInfo'|get_lang }}">
                                <i class="fa fa-clock-o" aria-hidden="true"></i>
                                 {{ course.time_spent }}
                            </div>
                            <div class="box" data-toggle="tooltip" data-placement="top" title="{{ 'AvgStudentsProgress'|get_lang }}">
                                <span class="kt-badge student-progress">
                                    {{ course.student_progress }} %
                                </span>
                            </div>
                            <div class="box" data-toggle="tooltip" data-placement="top" title="{{ 'AvgCourseScore'|get_lang }}">
                                <span class="kt-badge student-score">
                                    {{ course.student_score }}
                                </span>
                            </div>
                            <div class="box" data-toggle="tooltip" data-placement="top" title="{{ 'TotalNumberOfMessages'|get_lang }}">
                                <span class="kt-badge student-message">
                                    {{ course.student_message }}
                                </span>
                            </div>
                            <div class="box" data-toggle="tooltip" data-placement="top" title="{{ 'TotalNumberOfAssignments'|get_lang }}">
                                <span class="kt-badge student-assignments">
                                    {{ course.student_assignments }}
                                </span>
                            </div>
                            <div class="box">
                                <span class="kt-badge student-exercises" data-toggle="tooltip" data-placement="top" title="{{ 'TotalExercisesScoreObtained'|get_lang }}">
                                    {{ course.student_assignments }}
                                </span>
                            </div>
                            <div class="box">
                                <span class="kt-badge questions-answered" data-toggle="tooltip" data-placement="top" title="{{ 'TotalExercisesAnswered'|get_lang }}">
                                    {{ course.questions_answered }}
                                </span>
                            </div>
                            <div class="box box-date" data-toggle="tooltip" data-placement="top" title="{{ 'LatestLogin'|get_lang }}">
                                {% if course.last_connection  %}
                                <span class="kt-badge last-connection">
                                     {{ course.last_connection }}
                                </span>
                                {% endif %}
                            </div>
                        </div>
                    {% endfor %}
                    {% else %}
                        <div class="alert alert-warning" role="alert">
                            {{ 'HaveNoCourse'|get_lang }}
                        </div>
                    {% endif %}
                </div>

            </div>
        </div>
    </div>
</div>

