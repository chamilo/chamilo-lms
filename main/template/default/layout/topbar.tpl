<!-- Topbar -->
{% if show_toolbar == 1 %}
    <div id="topbar" class="navbar navbar-fixed-top">
        <div class="navbar-inner">
            <div class="container-fluid">
                <a data-toggle="collapse" data-target=".nav-collapse" class="btn btn-navbar">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </a>
                <a class="brand" href="{{ _p.web }}">{{"siteName"|get_setting }}</a>
                
                {% if _u.logged %}
                    
                <div class="nav-collapse">
                    <ul class="nav">
                        <li class="active"><a href="{{ _p.web }}user_portal.php">{{"MyCourses"|get_lang }}</a></li>
                        {# 
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">{{'Teaching'|get_lang }}<b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li><a href="{{ _p.web_main }}create_course/add_course.php">{{"AddCourse"|get_lang }}</a></li>
                                <li><a href="{{ _p.web_main }}auth/courses.php">{{"Catalog"|get_lang }}</a></li>
                            </ul>
                        </li>
                        #}
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">{{'Tracking'|get_lang }}<b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li><a href="{{ _p.web_main }}mySpace/">{{"CoursesReporting"|get_lang }}</a></li>
                                <li><a href="{{ _p.web_main }}mySpace/index.php?view=admin">{{"AdminReports"|get_lang }}</a></li>
                                <li><a href="{{ _p.web_main }}tracking/exams.php">{{"ExamsReporting"|get_lang }}</a></li>
                                <li class="divider"></li>
                                <li><a href="{{ _p.web_main }}dashboard/">{{"Dashboard"|get_lang }}</a></li>
                            </ul>
                        </li>
                        {% if _u.is_admin == 1 %}
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">{{'Administration'|get_lang }}<b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li><a href="{{ _p.web_main }}admin/">{{"Home"|get_lang }}</a></li>
                                <li><a href="{{ _p.web_main }}admin/user_list.php">{{"UserList"|get_lang }}</a></li>
                                <li><a href="{{ _p.web_main }}admin/course_list.php">{{"CourseList"|get_lang }}</a></li>
                                <li><a href="{{ _p.web_main }}admin/session_list.php">{{"SessionsList"|get_lang }}</a></li>
                                <li><a href="{{ _p.web_main }}admin/settings.php">{{"Settings"|get_lang }}</a></li>
                                <li class="divider"></li>
                                <li><a href="{{ _p.web_main }}admin/settings.php?category=Plugins">{{"Plugins"|get_lang }}</a></li>
                                <li><a href="{{ _p.web_main }}admin/settings.php?category=Regions">{{"Regions"|get_lang }}</a></li>
                            </ul>
                        </li>
                        {% endif %}
                    </ul>
                    {% endif %}

                    {% if _u.is_admin == 1 %}
                    <form class="navbar-search pull-left" action="{{ _p.web_main }}admin/user_list.php" method="get">
                        <input type="text" class="search-query span2" placeholder="{{'SearchUsers'|get_lang }}" name="keyword">
                    </form>
                    {% endif %}

                    {% if _u.logged %}
                    <ul class="nav pull-right">
                        <li><a href="{{ _p.web_main }}social/home.php"><img src="{{ _u.avatar_small }}"/></a></li>
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown"  href="#">{{ _u.complete_name }}<b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li><a href="{{ _p.web_main }}social/home.php">{{"Profile"|get_lang }}</a></li>
                                <li><a href="{{ _p.web_main }}calendar/agenda_js.php?type=personal">{{"MyAgenda"|get_lang }}</a></li>
                                <li><a href="{{ _p.web_main }}messages/inbox.php">{{"Messages"|get_lang }}</a></li>
                                <li><a href="{{ _p.web_main }}auth/my_progress.php">{{"MyReporting"|get_lang }}</a></li>
                                <!--<li class="divider"></li>
                                <li><a href="{{ _p.web_main }}social/invitations.php">{{"PendingInvitations"|get_lang }}</a></li> -->
                            </ul>
                        </li>
                        <li><a href="{{ _p.web }}index.php?logout=logout">{{"Logout"|get_lang }}</a></li>
                    </ul>
                    {% endif %}
                </div>         
            </div> <!-- /container-->
        </div><!-- /navbar-inner -->
    </div><!-- /navbar -->
    <div id="topbar_push"></div>
{% endif %}