<nav id="menubar" class="navbar navbar-default">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#menuone" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="menuone">
            <ul class="nav navbar-nav">
                {% for item in menu %}
                    <li class="{{ item.current }}"><a href="{{ item.url }}">{{ item.title }}</a></li>
                {% endfor %}
            </ul>
           {% if _u.logged == 1 %}
           <ul class="nav navbar-nav navbar-right">
               {% if user_notifications is not null %}
               <li><a href="{{ message_url }}">{{ user_notifications }}</a></li>
               {% endif %}
               {% if _u.status != 6 %}
                <li class="dropdown avatar-user">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                        <img class="img-circle" src="{{ _u.avatar_small }}" alt="{{ _u.complete_name }}" />  <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu" role="menu">
                        <li class="user-header">
                            <div class="text-center">
                            <img class="img-circle" src="{{ _u.avatar_medium }}" alt="{{ _u.complete_name }}" />
                            <p class="name"><a href="{{ profile_url }}">{{ _u.complete_name }}</a></p>
                            <p><i class="fa fa-envelope-o" aria-hidden="true"></i> {{ _u.email }}</p>
                            </div>
                        </li>
                        <li role="separator" class="divider"></li>
                        <li class="user-body">
                            <a title="{{ "Inbox"|get_lang }}" href="{{ message_url }}">
                                <em class="fa fa-envelope" aria-hidden="true"></em> {{ "Inbox"|get_lang }}
                            </a>
                            {% if certificate_url %}
                            <a title="{{ "MyCertificates"|get_lang }}" href="{{ certificate_url }}">
                                <em class="fa fa-graduation-cap" aria-hidden="true"></em> {{ "MyCertificates"|get_lang }}
                            </a>
                            {% endif %}

                            <a id="logout_button" title="{{ "Logout"|get_lang }}" href="{{ logout_link }}" >
                                <em class="fa fa-sign-out"></em> {{ "Logout"|get_lang }}
                            </a>
                        </li>
                    </ul>
                </li>
               {% endif %}
            </ul>
            {% endif %}
        </div>
    </div>
</nav>
