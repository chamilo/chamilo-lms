{% if menu is not null %}
    
<div class="navbar subnav">
    <div class="navbar-inner">
        <div class="container">            
            <a data-toggle="collapse" data-target=".nav-collapse" class="btn btn-navbar">                
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>
            <a class="brand" href="{{ _p.web }}">{{ "Institution" | get_setting }}</a>
            <div class="nav-collapse">
                <ul class="nav">
                    {{ menu }}
                </ul>

                {% if _u.logged == 1 %}
                <ul class="nav pull-right">
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
            </div>
        </div>
    </div>
</div>
{% endif %}