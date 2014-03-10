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

            {% if _u.logged  == 1 %}
            <ul class="nav main-menu">
                {% if 0 %}
                <li class="active">
                    <a href="{{ _p.web }}">
                        <i class="fa fa-home fa-lg"></i>
                        <span class="text">{{ 'Home' | get_lang }}</span>
                    </a>
                </li>
                {% endif %}

                <li class="active">
                    <a href="{{ _p.web_public }}main/dashboard/index.php">
                        <i class="fa fa-dashboard fa-lg"></i> {{ 'Dashboard' | get_lang }}
                    </a>
                </li>

                <li class="active">
                    <a href="{{ _p.web_main }}calendar/agenda_js.php?type=personal">
                        <i class="fa fa-calendar fa-lg"></i>
                        <span class="text">{{ 'Agenda' | get_lang }}</span>
                    </a>
                </li>

                <li class="active">
                    <a href="{{ _p.web }}main/social/home.php">
                        <i class="fa fa-user fa-lg"></i>
                        <span class="text">{{ 'Profile' | get_lang }}</span>
                    </a>
                </li>


                <li class="active">
                    <a href="{{ _p.web }}main/social/groups.php">
                        <i class="fa fa-users fa-lg"></i>
                        <span class="text">{{ 'Groups' | get_lang }}</span>
                    </a>
                </li>



                <li class="active">
                    <a href="{{ _p.web_main }}mySpace/index.php">
                        <i class="fa  fa-bar-chart-o fa-lg"></i>
                        <span class="text">{{ 'Reporting' | get_lang }}</span>
                    </a>
                </li>

                {% if _u.is_admin  == 1 %}
                    <li class="submenu">
                        <a href="#">
                            <i class="fa fa-cogs"></i>
                            <span class="text">{{ 'Administration' | get_lang }}
                            </span>
                            <i class="arrow fa fa-chevron-right"></i>
                        </a>
                        <ul class="nav nav-list" style="display:none">
                            <li>
                                <a href="{{ _p.web_main }}admin/index.php">
                                    <i class="fa fa-dashboard fa-lg"></i> {{ 'Dashboard' | get_lang }}
                                </a>
                            </li>

                            <li>
                                <a href="{{ _p.web_main }}admin/user_list.php">
                                    <i class="fa fa-user fa-lg"></i> {{ 'Users' | get_lang }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ _p.web_main }}admin/usergroups.php">
                                    <i class="fa fa-users fa-lg"></i>{{ 'Groups' | get_lang }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ _p.web_main }}admin/course_list.php">
                                    <i class="fa fa-book fa-lg"></i> {{ 'Courses' | get_lang }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ _p.web_main }}session/session_list.php">
                                    <i class="fa fa-sitemap fa-lg"></i>{{ 'Sessions'| get_lang }}
                                </a>
                            </li>

                        </ul>
                    </li>
                    {{ block_menu.title }}
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
            {% if _u.logged  == 0 %}
                {% include app.template_style ~ "/layout/login_form.tpl" %}
            {% endif %}

            {#  course_session_block #}
            {% include app.template_style ~ "/index/course_session_block.tpl" %}

            {#  User picture  #}
            {# include app.template_style ~ "/index/user_image_block.tpl" #}

            {#  User Profile links #}
            {# include app.template_style ~ "/index/profile_block.tpl" #}

            {#  Social links #}
            {# include app.template_style ~ "/index/profile_social_block.tpl" #}

            {#  Course block - admin #}
            {% include app.template_style ~ "/index/course_block.tpl" %}

            {#  Course block - teacher #}
            {% include app.template_style ~ "/index/teacher_block.tpl" %}

            {#  Session block #}
            {% include app.template_style ~ "/index/session_block.tpl" %}

            {#  Notice  #}
            {% include app.template_style ~ "/index/notice_block.tpl" %}

            {#  Help #}
            {% include app.template_style ~ "/index/help_block.tpl" %}

            {#  Links that are not added in the tabs #}
            {% include app.template_style ~ "/index/navigation_block.tpl" %}

            {#  Reservation block  #}
            {{ reservation_block }}

            {#  Search (xapian) #}
            {{ search_block }}

            {#  Classes  #}
            {{ classes_block }}

            {#  Skills #}
            {% include app.template_style ~ "/index/skills_block.tpl" %}

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
