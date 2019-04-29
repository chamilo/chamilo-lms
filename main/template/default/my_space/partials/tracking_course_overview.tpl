{% import 'default/macro/macro.tpl' as display %}

{% set content %}
    <div class="summary-course" id="summary-{{ data.id }}">
        <div class="row">
            <div class="col-md-2">
                <div class="course">
                    <h4 class="title">{{ data.title }}</h4>
                    <div class="image">
                        <img src="{{ data.image_small }}" class="img-responsive"/>
                    </div>
                    <div class="info">
                        {% if data.course_code %}
                            <p><strong>{{ 'WantedCourseCode'|get_lang }}</strong><br>{{ data.course_code }}</p>
                        {% endif %}
                        {% if data.category %}
                            <p><strong>{{ 'CourseFaculty'|get_lang }}</strong><br>{{ data.category }}</p>
                        {% endif %}
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="state">
                    <div class="stat-text">
                        <i class="fa fa-clock-o" aria-hidden="true"></i> {{ data.time_spent }}
                    </div>
                    <div class="stat-heading">
                        {{ 'TimeSpentInTheCourse'|get_lang }}
                    </div>
                </div>
                <div class="state">
                    <div class="stat-text">
                        {{ data.total_score }}
                    </div>
                    <div class="stat-heading">
                        {{ 'TotalExercisesScoreObtained'|get_lang }}
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="list-donut">
                    <div class="easy-donut">
                        <div class="easypiechart-blue easypiechart" title="{{ 'Progress'|get_lang }}"  data-percent="{{ data.avg_progress }}">
                            <span class="percent">{{ data.avg_progress }}%</span>
                        </div>
                        <div class="easypiechart-legend">
                            {{ 'AvgStudentsProgress'|get_lang }}
                        </div>
                    </div>
                    <div class="easy-donut">
                        <div class="easypiechart-red easypiechart" title="{{ 'Progress'|get_lang }}"  data-percent="{{ data.avg_score }}">
                            <span class="percent">{{ data.avg_score }}%</span>
                        </div>
                        <div class="easypiechart-legend">
                            {{ 'AvgCourseScore'|get_lang }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">

                <dl class="dl-horizontal list-info">
                    <dt>
                        <span title="{{ 'TotalNumberOfMessages'|get_lang }}">{{ 'TotalNumberOfMessages'|get_lang }}</span>
                    </dt>
                    <dd>
                        <span class="text-color"><i class="fa fa-comments" aria-hidden="true"></i></span>
                        <span class="text-color">{{ data.number_message }}</span>
                    </dd>
                    <dt>
                        <span title="{{ 'TotalNumberOfAssignments'|get_lang }}">{{ 'TotalNumberOfAssignments'|get_lang }}</span>
                    </dt>
                    <dd>
                        <span class="text-color"><i class="fa fa-pencil" aria-hidden="true"></i></span>
                        <span class="text-color">{{ data.number_assignments }}</span>
                    </dd>
                    <dt>
                        <span title="{{ 'TotalExercisesAnswered'|get_lang }}">{{ 'TotalExercisesAnswered'|get_lang }}</span>
                    </dt>
                    <dd>
                        <span class="text-color"><i class="fa fa-file-text" aria-hidden="true"></i></span>
                        <span class="text-color">{{ data.questions_answered }}</span>
                    </dd>
                    <dt>
                        <span title="{{ 'LatestLogin'|get_lang }}">{{ 'LatestLogin'|get_lang }}</span>
                    </dt>
                    <dd>
                        <span class="text-color"><i class="fa fa-clock-o" aria-hidden="true"></i></span>
                        <span class="text-color">{{ data.last_login }}</span>
                    </dd>

                </dl>

            </div>
        </div>
    </div>
{% endset %}

{{ display.panel('',content ,'') }}