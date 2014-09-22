<div class="navbar navbar-default navbar-static-top" class="hidden-xs">
    <div class="{{ containerClass }}">

    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#menu-collapse">
            <span class="sr-only"> {{ "Toggle navigation" | trans }}</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
    </div>
    <div id="menu-collapse" class="collapse navbar-collapse">
        <a class="navbar-brand col-lg-2 col-sm-1 col-xs-12" href="{{ url('index') }}">
            Chamilo
        </a>

        <ul class="nav navbar-nav">
            <li>
            <a id="main-menu-toggle" class="hidden-sidebar"><i class="fa fa-bars"></i></a>
            </li>
        </ul>

        {% render controller("ChamiloCoreBundle:Front:showMenu") %}

        {% if app.user %}
        <ul class="nav navbar-nav navbar-right">
            <li class="dropdown">
                <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                    <i class="fa fa-cog fa-lg"></i>
                </a>

                <ul class="pull-right dropdown-navbar navbar-pink dropdown-menu dropdown-caret dropdown-close">
                    <li class="dropdown-header">
                        <a href="#" id="template_settings"></a>
                    </li>
                </ul>

            </li>
            <li class="purple dropdown">
                <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                    <i class="fa fa-bell fa-lg"></i>
                    <span class="badge badge-important">{{ news_counter }}</span>
                </a>

                <ul class="pull-right dropdown-navbar navbar-pink dropdown-menu dropdown-caret dropdown-close">
                    <li class="dropdown-header">
                        <i class="icon-warning-sign"></i>
                        {{ news_counter }} Notifications
                    </li>

                    {% for new in news_list %}
                        <li>
                            <a href="#">
                                <div class="clearfix">
                                <span class="pull-left">
                                    <i class="btn btn-xs no-hover btn-pink icon-comment"></i>
                                    {{ new.title }}
                                </span>
                                    <span class="pull-right badge badge-info"></span>
                                </div>
                            </a>
                        </li>
                    {% endfor %}
                    <li>
                        <a href="#">
                            {{ 'SeeAllNotifications' | trans }}
                            <i class="fa fa-arrow-right"></i>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="green dropdown">
                <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                    <i class="fa fa-envelope fa-lg"></i>
                    <span class="badge badge-success">{{ messages_count }}</span>
                </a>
                <ul class="pull-right dropdown-navbar dropdown-menu dropdown-caret dropdown-close">
                    <li class="dropdown-header">
                        <i class="icon-envelope-alt"></i>
                        5 Messages
                    </li>
                    <li>
                        <a href="#">
                            <span class="msg-body">
                                <span class="msg-title">
                                    <span class="blue">joe doe:</span>
                                    Test message
                                </span>
                                <span class="msg-time">
                                    <i class="icon-time"></i>
                                    <span>a moment ago</span>
                                </span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href=" {{ message_link }}">
                            {{ 'SeeAllMessages' | trans }}
                            <i class="fa fa-arrow-right"></i>
                        </a>
                    </li>
                </ul>
            </li>

            {% if is_profile_editable == true %}
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        {% if ('allow_message_tool' | get_setting) == 'true' %}
                            {{ app.user.messages_count }}
                        {% endif %}
                        <img class="user-photo-nav" src="{{ _u.avatar_small }}"/>
                        {{ app.user.name }}
                        <b class="caret"></b>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="{{ profile_link }}">
                                <i class="fa fa-user"></i>
                                {{ 'Profile' | trans }}
                            </a>
                            <a href="{{ settings_link }}">
                                <i class="fa fa-cogs"></i>
                                {{ 'Settings' | trans }}
                            </a>
                        </li>
                    </ul>
                </li>
            {% else %}
                <li>
                    <a>
                        {% if ('allow_message_tool' | get_setting) == 'true' %}

                        {% endif %}
                        <img src="{{ app.user.username }}"/>
                        {{ app.user.username }}
                    </a>
                </li>
            {% endif %}

        </ul>
        {% endif %}
    </div>
</div>
