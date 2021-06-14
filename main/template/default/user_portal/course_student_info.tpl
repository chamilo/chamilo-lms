{% if student_info %}
    {% if not student_info.progress is null or not student_info.score is null or not student_info.certificate is null %}
        {% set num_columns = (student_info.progress is null ? 0 : 1) + (student_info.score is null ? 0 : 1) + (student_info.certificate is null ? 0 : 1) %}
        <div class="course-student-info">
            <div class="student-info">
                <hr>
                <div class="row">
                    {% if not student_info.progress is null %}
                        <div class="{{ num_columns == 1 ? 'col-xs-12' : (num_columns == 2 ? 'col-xs-9' : 'col-xs-6') }}">
                            <strong>{{ 'CourseAdvance'|get_lang }}</strong>
                            <div class="progress" style="margin-bottom: 0; height: 8px">
                                <div class="progress-bar progress-bar-info" role="progressbar"
                                     aria-valuenow="{{ student_info.progress }}" aria-valuemin="0" aria-valuemax="100"
                                     style="width: {{ student_info.progress }}%;background-color: #0077a1">
                                </div>
                            </div>
                            <small style="float:right;color:#0077a1">{{ 'XPercentCompleted'|get_lang|format(student_info.progress) }}</small>
                        </div>
                    {% endif %}
                    {% if not student_info.score is null %}
                        <div class="col-xs-3">
                            <div>{{ "StudentCourseScoreX" | get_lang | format(student_info.score) }}</div>
                        </div>
                    {% endif %}
                    {% if not student_info.certificate is null %}
                        <div class="col-xs-3">
                            <i class="fa fa-certificate text-warning" aria-hidden="true"></i>
                            {{ "StudentCourseCertificateX" | get_lang | format(student_info.certificate) }}
                        </div>
                    {% endif %}
                </div>
            </div>
        </div>
    {% endif %}
{% endif %}