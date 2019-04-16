<div class="details">
    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="user">
                        <div class="avatar">
                            <img width="128px" src="{{ user.avatar }}" class="img-responsive" >
                        </div>
                        <div class="name">
                            <h3>{{ user.complete_name_link }}</h3>
                            <p class="email">{{ user.email }}</p>
                        </div>
                        <div class="parameters">
                            <dl class="dl-horizontal">
                                <dt>{{ 'Tel'|get_lang }}</dt>
                                <dd>{{ user.phone == '' ? 'NoTel'|get_lang : user.phone }}</dd>
                                <dt>{{ 'OfficialCode'|get_lang }}</dt>
                                <dd>{{ user.code == '' ? 'NoOfficialCode'|get_lang : user.code }}</dd>
                                <dt>{{ 'OnLine'|get_lang }}</dt>
                                <dd>{{ user.online }}</dd>
                                <dt>{{ 'Timezone'|get_lang }}</dt>
                                <dd>{{ user.timezone }}</dd>
                            </dl>
                        </div>
                        <div class="access">
                            {{ user.url_access }}
                            {{ user.legal.url_send }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="row">
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="easy-donut">
                                <div id="easypiechart-blue" title="{{ 'Progress'|get_lang }}" class="easypiechart" data-percent="{{ user.student_progress }}">
                                    <span class="percent">{{ user.student_progress }}%</span>
                                </div>
                                <div class="easypiechart-legend">
                                    {{ 'ScormAndLPProgressTotalAverage'|get_lang }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="easy-donut">
                                <div id="easypiechart-red" title="{{ 'Score'|get_lang }}" class="easypiechart" data-percent="{{ user.student_score }}">
                                    <span class="percent">{{ user.student_score }} </span>
                                </div>
                                <div class="easypiechart-legend">
                                    {{ 'ScormAndLPTestTotalAverage'|get_lang }}
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
                                        <i class="fa fa-file-text-o" aria-hidden="true"></i>
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
                                        <i class="fa fa-file-text-o" aria-hidden="true"></i>
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
                </div>
                <div class="col-md-4">
                    <div class="card card-first-date">
                        <div class="card-body">
                            <div class="stat-widget-five">
                                <div class="stat-icon">
                                    <i class="fa fa-calendar" aria-hidden="true"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="text-left">
                                        <div class="stat-text">
                                            {{ user.first_connection }}
                                        </div>
                                        <div class="stat-heading">
                                            {{ 'FirstLoginInPlatform'|get_lang }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card card-last-date">
                        <div class="card-body">
                            <div class="stat-widget-five">
                                <div class="stat-icon">
                                    <i class="fa fa-calendar" aria-hidden="true"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="text-left">
                                        <div class="stat-text">
                                            {{ user.first_connection }}
                                        </div>
                                        <div class="stat-heading">
                                            {{ 'LatestLoginInPlatform'|get_lang }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {% if(user.time_spent_course) %}
                        <div class="card card-time">
                            <div class="card-body">
                                <div class="stat-widget-five">
                                    <div class="stat-icon">
                                        <i class="fa fa-clock-o" aria-hidden="true"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="text-left">
                                            <div class="stat-text">
                                                {{ user.time_spent_course }}
                                            </div>
                                            <div class="stat-heading">
                                                {{ 'TimeSpentInTheCourse'|get_lang }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    {% endif %}
                    <div class="card card-legal">
                        <div class="card-body">
                            <div class="stat-widget-five">
                                <div class="stat-icon">
                                    <i class="fa fa-gavel" aria-hidden="true"></i>
                                    <span class="active-icon">{{ user.legal.icon }}</span>
                                </div>
                                <div class="stat-content">
                                    <div class="text-left">
                                        <div class="stat-text">
                                            {{ user.legal.datetime }}
                                        </div>
                                        <div class="stat-heading">
                                            {{ 'LegalAccepted'|get_lang }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<script type="text/javascript">
    $(function() {
        $('#easypiechart-blue').easyPieChart({
            scaleColor: false,
            barColor: '#30a5ff',
            lineWidth:8,
            trackColor: '#f2f2f2'
        });
    });
    $(function() {
        $('#easypiechart-red').easyPieChart({
            scaleColor: false,
            barColor: '#f9243f',
            lineWidth:8,
            trackColor: '#f2f2f2'
        });
    });
</script>