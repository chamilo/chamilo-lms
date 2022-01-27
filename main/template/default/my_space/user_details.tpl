{% import 'default/macro/macro.tpl' as display %}

{% if title %}
    <h2 class="details-title"><img src="{{ 'course.png'|icon(32) }}"> {{ title }}</h2>
{% endif %}

<div class="page-header">
    <h3>{{ user.complete_name }}</h3>
</div>
<!-- NO DETAILS -->
{% if details != true %}
    <div class="no-details">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="user text-center">
                            <div class="avatar">
                                <img width="128px" src="{{ user.avatar }}" class="img-responsive">
                            </div>
                            <div class="name">
                                <h3>{{ user.complete_name_link }}</h3>
                                <p class="email">{{ user.email }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        {{ display.reporting_user_details(user) }}
                    </div>
                    <div class="col-md-4">
                        {{ display.card_widget('FirstLoginInPlatform'|get_lang, user.first_connection, 'calendar') }}
                        {{ display.card_widget('LatestLoginInPlatform'|get_lang, user.last_connection, 'calendar') }}
                        {{ display.card_widget('LatestLoginInAnyCourse'|get_lang, user.last_connection_in_course, 'calendar') }}

                        {% if user.legal %}
                            {{ display.card_widget('LegalAccepted'|get_lang, user.legal.datetime, 'gavel', user.legal.icon) }}
                        {% endif %}
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- DETAILS -->
{% else %}
    <div class="details">
        <div class="row">
            <div class="col-md-4">
                {{ display.panel('', display.reporting_user_box(user), '') }}
            </div>

            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="easy-donut">
                                    <div id="easypiechart-blue" title="{{ 'Progress'|get_lang }}" class="easypiechart"
                                         data-percent="{{ user.student_progress }}">
                                        <span class="percent">{{ user.student_progress }}%</span>
                                    </div>
                                    <div class="easypiechart-legend">
                                        {{ 'ScormAndLPProgressTotalAverage'|get_lang }}
                                    </div>
                                </div>
                            </div>
                            {% if not hide_lp_test_average %}
                            <div class="col-md-6">
                                <div class="easy-donut">
                                    <div id="easypiechart-red" title="{{ 'Score'|get_lang }}" class="easypiechart"
                                         data-percent="{{ user.student_score }}">
                                        <span class="percent">{{ user.student_score }} </span>
                                    </div>
                                    <div class="easypiechart-legend">
                                        {{ 'ScormAndLPTestTotalAverage'|get_lang }}
                                    </div>
                                </div>
                            </div>
                            {% endif %}
                        </div>

                        <div class="row">
                            <div class="col-md-8 col-md-offset-2">
                                <div class="card box-widget">
                                    <div class="card-body">
                                        <div class="stat-widget-five">
                                            <i class="fa fa-sign-in" aria-hidden="true"></i>
                                            {{ user.tools.count_access_dates }}
                                            <div class="box-name">
                                                {{ 'CountToolAccess'|get_lang }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card box-widget">
                                    <div class="card-body">
                                        <div class="stat-widget-five">
                                            <i class="fa fa-globe" aria-hidden="true"></i>
                                            {{ user.tools.links }}
                                            <div class="box-name">
                                                {{ 'LinksDetails'|get_lang }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card box-widget">
                                    <div class="card-body">
                                        <div class="stat-widget-five">
                                            <i class="fa fa-download" aria-hidden="true"></i>
                                            {{ user.tools.documents }}
                                            <div class="box-name">
                                                {{ 'DocumentsDetails'|get_lang }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card box-widget">
                                    <div class="card-body">
                                        <div class="stat-widget-five">
                                            <i class="fa fa-pencil" aria-hidden="true"></i>
                                            {{ user.tools.tasks }}
                                            <div class="box-name">
                                                {{ 'Student_publication'|get_lang }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card box-widget">
                                    <div class="card-body">
                                        <div class="stat-widget-five">
                                            <i class="fa fa-comments-o" aria-hidden="true"></i>
                                            {{ user.tools.messages }}
                                            <div class="box-name">
                                                {{ 'NumberOfPostsForThisUser'|get_lang }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card box-widget">
                                    <div class="card-body">
                                        <div class="stat-widget-five">
                                            <i class="fa fa-paper-plane" aria-hidden="true"></i>
                                            {{ user.tools.upload_documents }}
                                            <div class="box-name">
                                                {{ 'UploadedDocuments'|get_lang }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card box-widget">
                                    <div class="card-body">
                                        <div class="stat-widget-five">
                                            <i class="fa fa-plug" aria-hidden="true"></i>
                                            <span class="date" title="{{ user.tools.chat_connection }}">
                                        {% if user.tools.chat_connection != '' %}
                                            {{ user.tools.chat_connection }}
                                        {% else %}
                                            {{ 'NotRegistered'|get_lang }}
                                        {% endif %}
                                        </span>
                                            <div class="box-name">
                                                {{ 'ChatLastConnection'|get_lang }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {% if user.tools.count_access_dates %}
                        {% endif %}
                    </div>
                    <div class="col-md-4">
                        {% if user.tools.course_first_access %}
                            {{ display.card_widget('FirstLoginInCourse'|get_lang, user.tools.course_first_access, 'calendar-check-o', user.legal.icon) }}
                        {% endif %}

                        {% if user.tools.course_last_access %}
                            {{ display.card_widget('LatestLoginInCourse'|get_lang, user.tools.course_last_access, 'calendar-o', user.legal.icon) }}
                        {% endif %}

                        {% if user.time_spent_course %}
                            {{ display.card_widget('TimeSpentInTheCourse'|get_lang, user.time_spent_course, 'clock-o') }}
                        {% endif %}

                        {{ display.card_widget('FirstLoginInPlatform'|get_lang, user.first_connection, 'calendar') }}
                        {{ display.card_widget('LatestLoginInPlatform'|get_lang, user.last_connection, 'calendar') }}
                        {{ display.card_widget('LatestLoginInAnyCourse'|get_lang, user.last_connection_in_course, 'calendar') }}

                        {% if user.legal %}
                            {{ display.card_widget('LegalAccepted'|get_lang, user.legal.datetime, 'gavel', user.legal.icon) }}
                        {% endif %}
                    </div>
                </div>
            </div>
        </div>
    </div>
{% endif %}

<script>
    $(function () {
        $('#easypiechart-blue').easyPieChart({
            scaleColor: false,
            barColor: '#30a5ff',
            lineWidth: 8,
            trackColor: '#f2f2f2'
        });

        $('#easypiechart-red').easyPieChart({
            scaleColor: false,
            barColor: '#f9243f',
            lineWidth: 8,
            trackColor: '#f2f2f2'
        });
    });
</script>
