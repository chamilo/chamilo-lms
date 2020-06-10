<div class="search-student">
    {{ form }}
</div>

<div class="page-header">
    <h4>
        {{ 'Overview' | get_lang  }}
    </h4>
</div>

<div class="view-global-followed">
    <div class="row">
        <div class="col-lg-3 col-sm-3">
            <div class="card">
                <div class="content">
                    <div class="card-title"><a href="{{ _p.web_main }}mySpace/student.php">{{ 'FollowedStudents' | get_lang }}</a></div>
                    <div class="row">
                        <div class="col-xs-5">
                            <div class="icon-big icon-student text-center">
                                <i class="fa fa-graduation-cap" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="col-xs-7">
                            <div class="numbers">
                                <h2>{{ students }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-3">
            <div class="card">
                <div class="content">
                    <div class="card-title"><a href="{{ _p.web_main }}mySpace/users.php?status={{ studentboss }}">{{ 'FollowedStudentBosses' | get_lang }}</a></div>
                    <div class="row">
                        <div class="col-xs-5">
                            <div class="icon-big icon-studentboss text-center">
                                <i class="fa fa-address-book" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="col-xs-7">
                            <div class="numbers">
                                <h2>{{ studentbosses }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-3">
            <div class="card">
                <div class="content">
                    <div class="card-title"><a href="{{ _p.web_main }}mySpace/teachers.php">{{ 'FollowedTeachers' | get_lang }}</a></div>
                    <div class="row">
                        <div class="col-xs-5">
                            <div class="icon-big icon-teachers text-center">
                                <i class="fa fa-book" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="col-xs-7">
                            <div class="numbers">
                                <h2>{{ numberTeachers }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-3">
            <div class="card">
                <div class="content">
                    <div class="card-title"><a href="{{ _p.web_main }}mySpace/users.php?status={{ drh }}">{{ 'FollowedHumanResources' | get_lang }}</a></div>
                    <div class="row">
                        <div class="col-xs-5">
                            <div class="icon-big icon-humanresources text-center">
                                <i class="fa fa-user" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="col-xs-7">
                            <div class="numbers">
                                <h2>{{ humanresources }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="view-global">
    <div class="panel panel-default panel-view">
        <div class="panel-body">
            <div class="row">
                <div class="col-lg-3 col-sm-3">
                    <div class="easy-donut">
                        <div id="easypiechart-blue" class="easypiechart" data-percent="{{ total_user }}">
                            <span class="percent">{{ total_user }}</span>
                        </div>
                        <div class="easypiechart-link">
                            <a class="btn btn-default" href="{{ _p.web_main }}mySpace/users.php">
                                {{ 'FollowedUsers' | get_lang }}
                            </a>
                        </div>
                    </div>
                    {% if _u.status == 1 and _u.is_admin %}
                        <a href="{{ _p.web_main }}admin/dashboard_add_users_to_user.php?user={{ _u.id }}" class="btn btn-default btn-sm">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </a>
                    {% endif %}
                </div>
                <div class="col-lg-3 col-sm-3">
                    <div class="easy-donut">
                        <div id="easypiechart-orange" class="easypiechart" data-percent="{{ stats.courses }}">
                            <span class="percent">{{ stats.courses }}</span>
                        </div>
                        <div class="easypiechart-link">
                            <a class="btn btn-default" href="{{ _p.web_main }}mySpace/course.php">
                                {{ 'AssignedCourses' | get_lang }}
                            </a>
                        </div>
                    </div>
                    {% if _u.status == 1 %}
                        <a href="{{ _p.web_main }}mySpace/course.php" class="btn btn-default btn-sm">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </a>
                    {% endif %}
                </div>
                <div class="col-lg-3 col-sm-3">
                    <div  class="easy-donut">
                        <div id="easypiechart-teal" class="easypiechart" data-percent="{{ stats.assigned_courses }}">
                            <span class="percent">{{ stats.assigned_courses }}</span>
                        </div>
                        <div class="easypiechart-link">
                            <a class="btn btn-default" href="{{ _p.web_main }}mySpace/course.php?follow">{{ 'FollowedCourses' | get_lang }}</a>
                        </div>
                    </div>
                    {% if _u.status == 1 %}
                        <a href="{{ _p.web_main }}mySpace/course.php?follow" class="btn btn-default btn-sm">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </a>
                    {% endif %}
                </div>
                <div class="col-lg-3 col-sm-3">
                    <div class="easy-donut">
                        <div id="easypiechart-red" class="easypiechart" data-percent="{{ stats.session_list |length  }}">
                            <span class="percent">{{ stats.session_list |length }}</span>
                        </div>
                        <div class="easypiechart-link">
                            <a class="btn btn-default" href="{{ _p.web_main }}mySpace/session.php">{{ 'FollowedSessions' | get_lang }}</a>
                        </div>
                    </div>
                    {% if _u.status == 1 and _u.is_admin %}
                        <a href="{{ _p.web_main }}admin/dashboard_add_sessions_to_user.php?user={{ _u.id }}" class="btn btn-default btn-sm">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </a>
                    {% endif %}
                </div>
            </div>
        </div>
    </div>
</div>
<div class="page-header">
    <h4>
        {{ title }}
    </h4>
</div>
<div class="report-section">
    <div class="row">
        <div class="col-lg-3 col-sm-3">
            <div class="item-report">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="item-report-number">
                            {{ report.AverageCoursePerStudent }}
                        </div>
                    </div>
                </div>
                <p>{{ 'AverageCoursePerStudent' | get_lang }}</p>
            </div>
        </div>
        <div class="col-lg-3 col-sm-3">
            <div class="item-report">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="item-report-number">
                            {{ report.InactivesStudents }}
                        </div>
                    </div>
                </div>
                <p>{{ 'InactivesStudents' | get_lang }}</p>
            </div>
        </div>
        <div class="col-lg-3 col-sm-3">
            <div class="item-report">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="item-report-number">
                            {{ report.AverageTimeSpentOnThePlatform }}
                        </div>
                    </div>
                </div>
                <p>{{ 'AverageTimeSpentOnThePlatform' | get_lang }}</p>
            </div>
        </div>
        <div class="col-lg-3 col-sm-3">
            <div class="item-report">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="item-report-number">
                            {{ report.AverageProgressInLearnpath }}
                        </div>
                    </div>
                </div>
                <p>{{ 'AverageProgressInLearnpath' | get_lang }}</p>
            </div>
        </div>
        <div class="col-lg-3 col-sm-3">
            <div class="item-report">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="item-report-number">
                            {{ report.AvgCourseScore }}
                        </div>

                    </div>
                </div>
                <p>{{ 'AvgCourseScore' | get_lang }}</p>
            </div>
        </div>
        <div class="col-lg-3 col-sm-3">
            <div class="item-report">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="item-report-number">
                            {{ report.AveragePostsInForum }}
                        </div>

                    </div>
                </div>
                <p> {{ 'AveragePostsInForum' | get_lang }}</p>
            </div>
        </div>
        <div class="col-lg-3 col-sm-3">
            <div class="item-report">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="item-report-number">
                            {{ report.AverageAssignments }}
                        </div>

                    </div>
                </div>
                <p> {{ 'AverageAssignments' | get_lang }}</p>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        $('#easypiechart-teal').easyPieChart({
            scaleColor: false,
            barColor: '#1ebfae',
            lineWidth:8,
            trackColor: '#f2f2f2'
        });

        $('#easypiechart-orange').easyPieChart({
            scaleColor: false,
            barColor: '#ffb53e',
            lineWidth:8,
            trackColor: '#f2f2f2'
        });

        $('#easypiechart-red').easyPieChart({
            scaleColor: false,
            barColor: '#f9243f',
            lineWidth:8,
            trackColor: '#f2f2f2'
        });

        $('#easypiechart-blue').easyPieChart({
            scaleColor: false,
            barColor: '#30a5ff',
            lineWidth:8,
            trackColor: '#f2f2f2'
        });
    });
</script>
