<div class="navbar subnav">
    <div class="navbar-inner">
        <div class="container">
            <a data-toggle="collapse" data-target=".nav-collapse" class="btn btn-navbar">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>
            <a class="brand" href="{{ _p.web }}">{{ portal_name }}</a>
            <div class="nav-collapse">
                <ul class="nav">
                    {{ menu }}
                </ul>

                {% if _u.logged == 1 %}
                <ul class="nav pull-right">

                    {% if user_notifications is not null %}
                    <li class="notification-marker">
                        <a href="{{ message_url }}">{{ user_notifications }}</a>
                    </li>
                    {% endif %}

                    <li class="dropdown">

                        {% if _u.status != 6 %}

                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                            <img src="{{ _u.avatar_small }}"/>
                            {{ _u.complete_name }}
                            <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                {{ profile_link }}
                                {{ message_link }}
                            </li>
                        </ul>
                        {% endif %}

                    <li>
                        <a id="logout_button" class="logout" title="{{ "Logout"|get_lang }}" href="{{ logout_link }}" >
                            <img src="{{ "exit.png"|icon(22) }}">
                        </a>
                    </li>
                </ul>
                {% else %}

                    {# Direct login to course - no visible if logged and on the index page #}
                    {% if course_code != "" and hide_login_link is null %}
                        <ul class="nav pull-right">
                            <li class="dropdown" style="color:white;">
                                <a href='{{ _p.web }}main/auth/gotocourse.php?firstpage={{ course_code }}'>
                                    {{ "LoginEnter" | get_lang }}
                                </a>
                            </li>
                        </ul>
                    {% endif %}
                {% endif %}
            </div>
        </div>
    </div>
</div>
