<!-- Topbar -->
{% if show_toolbar == 1 %}
<nav id="toolbar-admin" class="navbar navbar-inverse">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="{{ _p.web }}">
                <img src="{{ "icon-chamilo.png"|icon(22) }}" title="{{ "siteName" | api_get_setting }}">
            </a>
        </div>

        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li class="active"><a href="{{ _p.web }}user_portal.php"> {{ "MyCourses"|get_lang }}</a></li>
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">{{'Tracking'|get_lang }}<b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a href="{{ _p.web_main }}mySpace/">{{ "CoursesReporting"|get_lang }}</a></li>
                        <li><a href="{{ _p.web_main }}mySpace/index.php?view=admin">{{ "AdminReports"|get_lang }}</a></li>
                        <li><a href="{{ _p.web_main }}tracking/exams.php">{{ "ExamsReporting"|get_lang }}</a></li>
                        <li class="divider"></li>
                        <li><a href="{{ _p.web_main }}dashboard/">{{ "Dashboard"|get_lang }}</a></li>
                    </ul>
                </li>
                {% if _u.logged %}
                {% if _u.is_admin == 1 %}
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">{{'Administration'|get_lang }}<b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a href="{{ _p.web_main }}admin/">{{ "Home"|get_lang }}</a></li>
                        <li><a href="{{ _p.web_main }}admin/user_list.php">{{ "UserList"|get_lang }}</a></li>
                        <li><a href="{{ _p.web_main }}admin/course_list.php">{{ "CourseList"|get_lang }}</a></li>
                        <li><a href="{{ _p.web_main }}session/session_list.php">{{ "SessionList"|get_lang }}</a></li>
                        <li class="divider"></li>
                        <li><a href="{{ _p.web_main }}admin/settings.php">{{ "Settings"|get_lang }}</a></li>
                        <li class="divider"></li>
                        <li><a href="{{ _p.web_main }}admin/settings.php?category=Plugins">{{ "Plugins"|get_lang }}</a></li>
                        <li><a href="{{ _p.web_main }}admin/settings.php?category=Regions">{{ "Regions"|get_lang }}</a></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">{{ 'Add'|get_lang }}<b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a href="{{ _p.web_main }}admin/user_add.php">{{ "User"|get_lang }}</a></li>
                        <li><a href="{{ _p.web_main }}admin/course_add.php">{{ "Course"|get_lang }}</a></li>
                        <li><a href="{{ _p.web_main }}session/session_add.php">{{ "Session"|get_lang }}</a></li>
                    </ul>
                </li>
                {% endif %}
            </ul>

            {% if _u.is_admin == 1 %}
            <form class="navbar-form navbar-left" role="search" action="{{ _p.web_main }}admin/user_list.php" method="get">
                <input type="text" class="form-control" placeholder="{{'SearchUsers'|get_lang }}" name="keyword">
                <button type="submit" class="btn btn-primary">{{'Search'|get_lang }}</button>
            </form>
            {% endif %}

            <ul class="nav navbar-nav navbar-right">
                <li><a href="{{  _p.web }}index.php?logout=logout&uid={{_u.user_id}}">{{ "Logout"|get_lang }}</a></li>
            </ul>
            {% endif %}
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>
{% endif %}
