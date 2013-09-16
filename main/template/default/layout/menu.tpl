{% if menu is not null %}
<div class="navbar subnav">
    <div class="navbar-inner">
        {% if app.full_width == 1 %}
            <div id="main" class="container-fluid">
        {% else %}
            <div id="main" class="container">
        {% endif %}
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
                    {% if is_profile_editable == true %}
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                                {% if ('allow_message_tool' | get_setting) == 'true' %}
                                    {{ _u.messages_count }}
                                {% endif %}
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
                        </li>
                    {% else %}
                        <li>
                            <a>
                                {% if ('allow_message_tool' | get_setting) == 'true' %}
                                    {{ _u.messages_count }}
                                {% endif %}
                                <img src="{{ _u.avatar_small }}"/>
                                {{ _u.complete_name }}
                            </a>
                        </li>
                    {% endif %}
                    <li>
                        <a id="logout_button" class="logout" title="{{ "Logout"|get_lang }}" href="{{ url('admin_logout') }}" >
                            <img src="{{ "exit.png"|icon(22) }}">
                        </a>
                    </li>
                </ul>
                {% endif %}
            </div>
        </div>
    </div>
</div>
{% endif %}
