<div id="sidebar-left" class="col-lg-2 col-sm-1">
    <nav class="nav nav-tabs nav-stacked main-menu">
        <div id="sidebar" class="sidebar-collapse">
            {% if 0 %}
                <div class="shortcuts">
                    <div class="shortcuts-large">
                        <a class="btn btn-success" href="{{ _p.web_main }}calendar/agenda_js.php?type=personal">
                            <i class="fa fa-calendar"></i>
                        </a>
                        <a class="btn btn-info" href="{{ _p.web_main }}mySpace/index.php">
                            <i class="fa fa-signal"></i>
                        </a>
                        <a class="btn btn-warning" href="{{ _p.web_main }}social/home.php">
                            <i class="fa fa-users"></i>
                        </a>
                        <a class="btn btn-danger" href="{{ settings_link }}">
                            <i class="fa fa-cogs"></i>
                        </a>
                    </div>
                    <div class="shortcuts-mini" style="display:none">
                        c
                    </div>
                </div>
            {% endif %}

            {% if app.user %}
            <ul class="nav main-menu">
                {% if 0 %}
                <li class="active">
                    <a href="{{ url('index') }}">
                        <i class="fa fa-home fa-lg"></i>
                        <span class="text">{{ 'Home' | trans }}</span>
                    </a>
                </li>
                {% endif %}

                <li class="active">
                    <a href="{{ url('index') }}">
                        <i class="fa fa-dashboard fa-lg"></i>
                        <span class="text">{{ 'Dashboard' | trans }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ url('index') }}">
                        <i class="fa fa-book fa-lg"></i>
                        <span class="text">{{ 'Courses' | trans }}</span>
                    </a>
                </li>

                <li class="active">
                    <a href="{{ url('index') }}">
                        <i class="fa fa-users fa-lg"></i>
                        <span class="text">{{ 'Groups' | trans }}</span>
                    </a>
                </li>

                <li class="active">
                    <a href="{{ url('index') }}">
                        <i class="fa fa-users fa-lg"></i>
                        <span class="text">{{ 'Sessions' | trans }}</span>
                    </a>
                </li>

                {% if app.user %}
                    <li class="submenu">
                        <a href="#">
                            <i class="fa fa-cogs"></i>
                            <span class="text">{{ 'Administration' | trans }}
                            </span>
                            <i class="arrow fa fa-chevron-right"></i>
                        </a>
                        <ul class="nav nav-list" style="display:none">
                            <li>
                                <a href="/main/admin/index.php">
                                    <i class="fa fa-dashboard fa-lg"></i> {{ 'Dashboard' | trans }}
                                </a>
                            </li>

                            <li>
                                <a href="/main/admin/user_list.php">
                                    <i class="fa fa-user fa-lg"></i> {{ 'Users' | trans }}
                                </a>
                            </li>
                            <li>
                                <a href="/main/admin/usergroups.php">
                                    <i class="fa fa-users fa-lg"></i>{{ 'Groups' | trans }}
                                </a>
                            </li>
                            <li>
                                <a href="/main/admin/course_list.php">
                                    <i class="fa fa-book fa-lg"></i> {{ 'Courses' | trans }}
                                </a>
                            </li>
                            <li>
                                <a href="/main/session/session_list.php">
                                    <i class="fa fa-sitemap fa-lg"></i>{{ 'Sessions'| trans }}
                                </a>
                            </li>

                        </ul>
                    </li>
                {% endif %}
            </ul>
            {% endif %}

            {#  Left column  #}
            {% if plugin_menu_top %}
                <div id="plugin_menu_top">
                    {{plugin_menu_top}}
                </div>
            {% endif %}

            {# if user is not login show the login form #}
            {# render (controller('FOSUserBundle:Security:login')) #}

            {#  course_session_block #}
            {# render controller("ChamiloLMSCoreBundle:Front:showCourseSessionBlock") %}

            {#  User picture  #}
            {# include "@template_style/index/user_image_block.tpl" #}

            {#  User Profile links #}
            {# include "@template_style/index/profile_block.tpl" #}

            {#  Social links #}
            {# include "@template_style/index/profile_social_block.tpl" #}

            {#  Course block - admin #}
            {# render controller("ChamiloLMSCoreBundle:Front:showCourseBlock") %}

            {#  Course block - teacher #}
            {# render controller("ChamiloLMSCoreBundle:Front:showTeacherBlock") %}

            {#  Session block #}
            {# render controller("ChamiloLMSCoreBundle:Front:showSessionBlock") %}

            {# render controller("ChamiloLMSCoreBundle:Front:showNoticeBlock") %}

            {# render controller("ChamiloLMSCoreBundle:Front:showHelpBlock") %}

            {% render controller("ChamiloLMSCoreBundle:Front:showNavigationBlock") %}

            {% render controller("ChamiloLMSCoreBundle:Front:showSkillsBlock") %}

            {#  Plugin courses sidebar  #}
            {#  Plugins for footer section  #}

            {% if plugin_menu_bottom %}
                <div id="plugin_menu_bottom">
                    {{ plugin_menu_bottom }}
                </div>
            {% endif %}
        </div>
    </nav>
</div>
