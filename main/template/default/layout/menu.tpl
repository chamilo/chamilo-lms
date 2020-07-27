<!-- Fixed navbar -->
{% if _u.logged == 1 and not user_in_anon_survey %}
    <script>
        $(function () {
            $.get('{{ _p.web_main }}inc/ajax/message.ajax.php?a=get_count_message', function(data) {
                var countNotifications = (data.ms_friends + data.ms_groups + data.ms_inbox);
                if (countNotifications === 0 || isNaN(countNotifications)) {
                    $("#count_message_li").addClass('hidden');
                } else {
                    $("#count_message_li").removeClass('hidden');
                    $("#count_message").append(countNotifications);
                }
            });
        });
    </script>
{% endif %}
<nav class="navbar navbar-default">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="pull-right  navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            {% if _u.logged == 1 and notification_event == 1 %}
                <button id="user-dropdown"  type="button" class="menu-dropdown pull-right navbar-toggle collapsed"
                        data-toggle="collapse" data-target="#user-dropdown-menu" aria-expanded="false" aria-controls="navbar">
                    <img class="img-circle" src="{{ _u.avatar_small }}" alt="{{ _u.complete_name }}"/>
                    <span class="caret"></span>

                    <ul id="user-dropdown-menu" class="dropdown-menu" role="menu" aria-labelledby="user-dropdown">
                        <li class="user-header">
                            <div class="text-center">
                                <a href="{{ profile_url }}">
                                    <img class="img-circle" src="{{ _u.avatar_medium }}" alt="{{ _u.complete_name }}"/>
                                    <p class="name">{{ _u.complete_name }}</p>
                                </a>
                                <p><em class="fa fa-envelope-o" aria-hidden="true"></em> {{ _u.email }}</p>
                            </div>
                        </li>
                        <li role="separator" class="divider"></li>
                        {% if message_url %}
                            <li class="user-body">
                                <a title="{{ "Inbox"|get_lang }}" href="{{ message_url }}">
                                    <em class="fa fa-envelope" aria-hidden="true"></em> {{ "Inbox"|get_lang }}
                                </a>
                            </li>
                        {% endif %}

                        {% if pending_survey_url %}
                            <li class="user-body">
                                <a href="{{ pending_survey_url }}">
                                    <em class="fa fa-pie-chart"></em> {{ 'PendingSurveys'|get_lang }}
                                </a>
                            </li>
                        {% endif %}

                        {% if certificate_url %}
                            <li class="user-body">
                                <a title="{{ "MyCertificates"|get_lang }}" href="{{ certificate_url }}">
                                    <em class="fa fa-graduation-cap"
                                        aria-hidden="true"></em> {{ "MyCertificates"|get_lang }}
                                </a>
                            </li>
                        {% endif %}

                        <li class="user-body">
                            <a id="logout_button" title="{{ "Logout"|get_lang }}" href="{{ logout_link }}">
                                <em class="fa fa-sign-out"></em> {{ "Logout"|get_lang }}
                            </a>
                        </li>
                    </ul>
                </button>

                {% if _u.logged == 1 %}
                    <button id="notifications-dropdown" type="button" class="pull-right menu-dropdown navbar-toggle collapsed"
                            data-toggle="collapse" data-target="#notification-menu" aria-expanded="false" aria-controls="navbar">
                        <em id="notificationsIcon" class="fa fa-bell-o " aria-hidden="true"></em>
                        {# hide red button loading #}
                        <span id="notificationsBadge" class="label label-danger">
                        <em class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></em>
                        </span>
                        <div id="notification-menu"
                             class="dropdown-menu notification-dropdown-menu" aria-labelledby="notifications-dropdown">
                            <h5 class="dropdown-header">
                                <em class="fa fa-bell-o" aria-hidden="true"></em> <span class="fw-600 c-grey-900">
                                    {{ 'Notifications' | get_lang }}
                                </span>
                            </h5>
                            <a id="notificationsLoader" class="dropdown-item dropdown-notification" href="#">
                                <p class="notification-solo text-center">
                                    <em id="notificationsIcon" class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></em>
                                    {{ 'Loading' | get_lang }}
                                </p>
                            </a>
                            <ul id="notificationsContainer" class="notifications-container"></ul>
                            <a id="notificationEmpty" class="dropdown-item dropdown-notification" href="#">
                                <p class="notification-solo text-center"> {{ 'NoNewNotification' | get_lang }}</p>
                            </a>
                        </div>
                    </button>

                    <button id="count_message_li" type="button" class="pull-right navbar-toggle collapsed menu-dropdown" aria-expanded="true">
                        <a href="{{ message_url }}">
                            <span id="count_message" class="badge badge-warning"></span>
                        </a>
                    </button>
                {% endif %}
            {% endif %}
            <a class="navbar-brand" href="{{ _p.web }}"> <em class="fa fa-home"></em> </a>
        </div>

        <div id="navbar" class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
                {% for item in menu %}
                    {% set show_item = true %}
                    {% if user_in_anon_survey and item.key != 'homepage' %}
                        {% set show_item = false %}
                    {% endif %}

                    {% if show_item %}
                        <li class="{{ item.key }} {{ item.current }}">
                            <a href="{{ item.url }}" {{ item.target ? 'target="' ~ item.target ~ '"' : '' }} title="{{ item.title }}">
                                {{ item.title }}
                            </a>
                        </li>
                    {% endif %}
                {% endfor %}
            </ul>
            {% if _u.logged == 1 and not user_in_anon_survey %}
                <ul class="nav navbar-nav navbar-right">
                    {% if language_form %}
                        <li class="dropdown language">
                            {{ language_form }}
                        </li>
                    {% endif %}
                    {% if notification_event == 0 %}
                        {% if _u.status != 6 %}
                            <li id="count_message_li" class="pull-left " style="float: left !important;" aria-expanded="true">
                                <a href="{{ message_url }}">
                                    <span id="count_message" class="badge badge-warning"></span>
                                </a>
                            </li>
                            <li class="dropdown avatar-user" style="float:right">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                                   aria-expanded="false">
                                    <img class="img-circle" src="{{ _u.avatar_small }}" alt="{{ _u.complete_name }}"/>
                                    <span class="username-movil">{{ _u.complete_name }}</span>
                                    <span class="caret"></span>
                                </a>
                                <ul class="dropdown-menu" role="menu">
                                    <li class="user-header">
                                        <div class="text-center">
                                            <a href="{{ profile_url }}">
                                                <img class="img-circle" src="{{ _u.avatar_medium }}" alt="{{ _u.complete_name }}"/>
                                                <p class="name">{{ _u.complete_name }}</p>
                                            </a>
                                            <p><em class="fa fa-envelope-o" aria-hidden="true"></em> {{ _u.email }}</p>
                                        </div>
                                    </li>
                                    <li role="separator" class="divider"></li>
                                    {% if message_url %}
                                        <li class="user-body">
                                            <a title="{{ "Inbox"|get_lang }}" href="{{ message_url }}">
                                                <em class="fa fa-envelope" aria-hidden="true"></em> {{ "Inbox"|get_lang }}
                                            </a>
                                        </li>
                                    {% endif %}

                                    {% if pending_survey_url %}
                                        <li class="user-body">
                                            <a href="{{ pending_survey_url }}">
                                                <em class="fa fa-pie-chart"></em> {{ 'PendingSurveys'|get_lang }}
                                            </a>
                                        </li>
                                    {% endif %}

                                    {% if certificate_url %}
                                        <li class="user-body">
                                            <a title="{{ "MyCertificates"|get_lang }}" href="{{ certificate_url }}">
                                                <em class="fa fa-graduation-cap"
                                                    aria-hidden="true"></em> {{ "MyCertificates"|get_lang }}
                                            </a>
                                        </li>
                                    {% endif %}

                                    <li class="user-body">
                                        <a id="logout_button" title="{{ "Logout"|get_lang }}" href="{{ logout_link }}">
                                            <em class="fa fa-sign-out"></em> {{ "Logout"|get_lang }}
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        {% endif %}
                    {% endif %}

                    {% if notification_event == 1 %}
                        {% include 'default/layout/notification.tpl' %}
                    {% endif %}
                </ul>
            {% endif %}
        </div><!--/.nav-collapse -->
    </div>
</nav>