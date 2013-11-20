{% if menu is not null %}
<div class="navbar navbar-default nav-menu">

    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#menu-collapse">
            <span class="sr-only"> {{ "Toggle navigation" | trans }}</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
    </div>

    <div id="menu-collapse" class="navbar-collapse collapse">
        <ul class="nav navbar-nav">
            {{ menu }}
        </ul>

        {% if _u.logged == 1 %}
        <ul class="nav navbar-nav navbar-right">
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
                <a id="logout_button" class="logout" title="{{ "Logout"|get_lang }}" href="{{ url('logout') }}" >
                    <img src="{{ "exit.png"|icon(22) }}">
                </a>
            </li>
        </ul>
        {% endif %}
    </div>
</div>
{% endif %}
