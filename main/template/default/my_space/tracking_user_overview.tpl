<div class="summary-legend">
    <ul class="list-legend">
        <li>
            <span class="cube student-progress">
            </span>
            {{ 'AvgStudentsProgress'|get_lang }}
        </li>
        <li>
            <span class="cube student-score">
            </span>
            {{ 'AvgCourseScore'|get_lang }}
        </li>
        <li>
            <span class="cube student-message">
            </span>
            {{ 'TotalNumberOfMessages'|get_lang }}
        </li>
        <li>
            <span class="cube student-assignments">
            </span>
            {{ 'TotalNumberOfAssignments'|get_lang }}
        </li>
        <li>
            <span class="cube student-exercises">
            </span>
            {{ 'TotalExercisesScoreObtained'|get_lang }}
        </li>
        <li>
            <span class="cube questions-answered">
            </span>
            {{ 'TotalExercisesAnswered'|get_lang }}
        </li>
        <li>
            <span class="cube last-connection">
            </span>
            {{ 'LatestLogin'|get_lang }}
        </li>
    </ul>
</div>

{% for item in data %}
<div class="summary-height">
    <div class="summary">
        <div class="summary-body">
            <div id="summary-user-{{ item.id }}" class="summary-item">
                <div class="icon">
                    <img src="{{ item.avatar }}" class="img-circle">
                </div>
                <div class="user">
                    <a href="{{ _p.web }}main/social/profile.php?u={{ item.id }}" class="name">
                        {{ item.complete_name }}
                    </a>
                    <div class="username">{{ item.username }}</div>
                </div>

                <div class="summary-course">
                    {% if item.course %}
                    {% for course in item.course %}
                        <div id="course-{{ course.real_id }}" class="course-item">
                            <div class="course-info">
                                <h5>{{ course.title }}</h5>
                                <span class="code">{{ course.code }}</span>
                            </div>
                            <div class="box time-spent">
                                <i class="fa fa-clock-o" aria-hidden="true"></i>
                                 {{ course.time_spent }}
                            </div>
                            <div class="box">
                                <span class="kt-badge student-progress">
                                    {{ course.student_progress }} %
                                </span>
                            </div>
                            <div class="box">
                                <span class="kt-badge student-score">
                                    {{ course.student_score }}
                                </span>
                            </div>
                            <div class="box">
                                <span class="kt-badge student-message">
                                    {{ course.student_message }}
                                </span>
                            </div>
                            <div class="box">
                                <span class="kt-badge student-assignments">
                                    {{ course.student_assignments }}
                                </span>
                            </div>
                            <div class="box">
                                <span class="kt-badge student-exercises">
                                    {{ course.student_assignments }}
                                </span>
                            </div>
                            <div class="box">
                                <span class="kt-badge questions-answered">
                                    {{ course.questions_answered }}
                                </span>
                            </div>
                            <div class="box">
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
                            Sin registros
                        </div>
                    {% endif %}
                </div>

            </div>
        </div>
    </div>
</div>
{% endfor %}
