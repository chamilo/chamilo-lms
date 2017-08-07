<div class="container">
    <nav class="navbar navbar-default">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#menuone" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="{{ _p.web }}">
               {{ _s.site_name }}
            </a>
        </div>
        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="menuone">
            <ul class="nav navbar-nav">
                {% if _u.logged == 1 %}
                    {% for items in menu %}
                        {% if items.key != 'profile' and items.key != 'dashboard' and items.key != 'admin' and items.key != 'agenda' %}
                        {% set counter = counter + 1 %}
                            <li class="item-menu menu-{{ counter }} {{ items.key }} {{ items.current }}">
                                <a href="{{ items.url }}">{{ items.title }}</a>
                            </li>
                        {% endif %}
                    {% endfor %}
                {% endif %}
                {% if _u.logged == 1 %}
                    <li class="item-menu menu-5"><a href="{{ _p.web_url }}faq?_locale={{ document_language }}">{{ "FAQ"|get_lang }}</a></li>
                {% endif %}

                {% if _u.logged == 0 %}
                    <li class="item-menu {% if _p.basename == 'index.php' %} active {% endif %}">
                        <a href="{{ _p.web }}?language={{ current_language }}">{{ "CampusHomepage"|get_lang }}</a>
                    </li>
                    <li class="item-menu menu-2">
                        <a href="{{ _p.web_url }}faq?_locale={{ document_language }}">
                            {{ "FAQ"|get_lang }}
                        </a>
                    </li>
                    <li class="item-menu menu-3 {% if _p.basename == 'inscription.php' %} active {% endif %}">
                        <a href="{{ _p.web }}main/auth/inscription.php?language={{ current_language }}">{{ "Subscription"|get_lang }}</a>
                    </li>
                    <li class="item-menu menu-4"><a href="{{ "DemoMenuLink"|get_lang }}">{{ "Demo"|get_lang }}</a></li>
                    <li class="item-menu menu-5"><a href="{{ _p.web_url }}contact?_locale={{ document_language }}">{{ "Contact"|get_lang }}</a></li>
                {% endif %}
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
                            {% if _u.is_admin == 1 %}
                            <a href="{{ _p.web }}main/admin">
                                <em class="fa fa-cog" aria-hidden="true"></em>
                                {{ "NomPageAdmin"|get_lang }}
                            </a>
                            {% endif %}
                            <a href="{{ _p.web }}main/calendar/agenda_js.php?type=personal">
                                <em class="fa fa-calendar" aria-hidden="true"></em>
                                {{ "AllowPersonalAgendaTitle"|get_lang }}
                            </a>
                            <a title="{{ "Inbox"|get_lang }}" href="{{ message_url }}">
                                <em class="fa fa-envelope" aria-hidden="true"></em> {{ "Inbox"|get_lang }}
                            </a>
                            <a title="{{ "MyCertificates"|get_lang }}" href="{{ certificate_url }}">
                                <em class="fa fa-graduation-cap" aria-hidden="true"></em> {{ "MyCertificates"|get_lang }}
                            </a>
                            <a id="logout_button" title="{{ "Logout"|get_lang }}" href="{{ logout_link }}" >
                                <em class="fa fa-sign-out"></em> {{ "Logout"|get_lang }}
                            </a>
                        </li>
                    </ul>
                </li>
               {% endif %}
            </ul>
            {% endif %}
        </div><!-- /.navbar-collapse -->
    </nav>
</div><!-- /.container-fluid -->
