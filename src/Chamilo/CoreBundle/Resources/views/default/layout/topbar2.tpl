<!-- Topbar -->
{% if show_toolbar == 1 %}

    <nav id="topbar" class="navbar navbar-default navbar-fixed-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#topbar-collapse">
                    <span class="sr-only"> {{ "Toggle navigation" | trans }}</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                <a class="navbar-brand" href="{{ _p.web }}">
                    {{ "siteName" | get_setting }}
                </a>
            </div>

            {% if _u.logged %}
                <div id="topbar-collapse" class="collapse navbar-collapse">
                    <ul class="nav navbar-nav">
                        <li class="active">
                            <a href="{{ _p.web }}user_portal.php"> {{ "MyCourses"|trans }}</a>
                        </li>
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                                {{'Tracking'|trans }}<b class="caret"></b>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="{{ _p.web_main }}mySpace/">{{ "CoursesReporting"|trans }}</a></li>
                                <li><a href="{{ _p.web_main }}mySpace/index.php?view=admin">{{ "AdminReports"|trans }}</a></li>
                                <li><a href="{{ _p.web_main }}tracking/exams.php">{{ "ExamsReporting"|trans }}</a></li>
                                <li class="divider"></li>
                                <li><a href="{{ _p.web_main }}dashboard/">{{ "Dashboard"|trans }}</a></li>
                            </ul>
                        </li>
                        {% if _u.is_admin == 1 %}
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">{{'Administration'|trans }}<b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li><a href="{{ _p.web_main }}admin/">{{ "Home"|trans }}</a></li>
                                <li><a href="{{ _p.web_main }}admin/user_list.php">{{ "UserList"|trans }}</a></li>
                                <li><a href="{{ _p.web_main }}admin/course_list.php">{{ "CourseList"|trans }}</a></li>
                                <li><a href="{{ _p.web_main }}session/session_list.php">{{ "SessionList"|trans }}</a></li>
                                <li class="divider"></li>
                                <li><a href="{{ _p.web_main }}admin/settings.php">{{ "Settings"|trans }}</a></li>
                                <li class="divider"></li>
                                <li><a href="{{ _p.web_main }}admin/settings.php?category=Plugins">{{ "Plugins"|trans }}</a></li>
                                <li><a href="{{ _p.web_main }}admin/settings.php?category=Regions">{{ "Regions"|trans }}</a></li>
                            </ul>
                        </li>

                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">{{ 'Add'|trans }}<b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li><a href="{{ _p.web_main }}admin/user_add.php">{{ "User"|trans }}</a></li>
                                <li><a href="{{ _p.web_main }}admin/course_add.php">{{ "Course"|trans }}</a></li>
                                <li><a href="{{ _p.web_main }}session/session_add.php">{{ "Session"|trans }}</a></li>
                            </ul>
                        </li>
                        {% endif %}
                    </ul>

                    {% if _u.is_admin == 1 %}
                    <form class="navbar-form navbar-left" action="{{ _p.web_main }}admin/user_list.php" method="get">
                        <div class="form-group">
                            <input type="text" class="form-control" placeholder="{{'SearchUsers'|trans }}" name="keyword">
                        </div>
                    </form>
                    {% endif %}

                    <ul class="nav navbar-nav navbar-right">
                        <li>
                            <a href="{{ _p.web_main }}social/home.php"><img src="{{ _u.avatar_small }}" /></a>
                        </li>
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">{{ _u.complete_name }}<b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li><a href="{{ _p.web_main }}social/home.php">{{ "Profile"|trans }}</a></li>
                                <li><a href="{{ _p.web_main }}calendar/agenda_js.php?type=personal">{{ "MyAgenda"|trans }}</a></li>
                                <li><a href="{{ _p.web_main }}messages/inbox.php">{{ "Inbox"|trans }}</a></li>
                                <li><a href="{{ _p.web_main }}auth/my_progress.php">{{ "MyReporting"|trans }}</a></li>
                                <!--<li class="divider"></li>
                                <li><a href="{{ _p.web_main }}social/invitations.php">{{ "PendingInvitations"|trans }}</a></li> -->
                            </ul>
                        </li>
                        <li><a href="{{ _p.web_public }}logout">{{ "Logout"|trans }}</a></li>
                    </ul>
                </div> <!-- /nav collapse -->
            {% endif %}
        </div> <!-- /container-->
    </nav><!-- /topbar -->
{% endif %}
