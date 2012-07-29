{% if menu is not null %}
    <div class="subnav">
        {% if _u.logged == 1 %}
        <ul class="nav nav-pills pull-right">
            <li class="dropdown">
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
            
            <li>
                <a class="logout" title="{{ "Logout" | get_lang }}" href="{{ logout_link }}" >
                    <img src="{{ "exit.png" | icon(22) }}">
                </a>
            </li>            
        </ul>
        {% endif %}
         
        <ul class="nav nav-pills">
            {{ menu }}
        </ul>
    </div>
{% endif %}