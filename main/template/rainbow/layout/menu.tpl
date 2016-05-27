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
        </div>
        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="menuone">
            <ul class="nav navbar-nav">
                
                {% if _u.logged == 1 %}
                    {% for items in menu %}
                        {% if items.key != 'profile' and items.key != 'dashboard' and items.key != 'admin' and items.key != 'agenda' %}
                        {% set counter = counter + 1 %}
                            <li class="item-menu menu-{{ counter }} {{ items.key }} {{ items.current }}"><a href="{{ items.url }}">{{ items.title }}</a></li>
                        {% endif %}
                    {% endfor %}
                {% endif %}
                {% if _u.logged == 0 %}
                    <li class="item-menu menu-1"><a href="{{ _p.web }}">{{ "CampusHomepage"|get_lang }}</a></li>
                {% endif %}
                    <li class="item-menu menu-5"><a href="{{ _p.web }}web/app_dev.php/faq">{{ "FAQ"|get_lang }}</a></li>
                {% if _u.logged == 0 %}
                    <li class="item-menu menu-3"><a href="{{ _p.web }}main/auth/inscription.php">{{ "Subscription"|get_lang }}</a></li>
                    <li class="item-menu menu-4"><a href="#">{{ "Demo"|get_lang }}</a></li>
                    <li class="item-menu menu-5"><a href="{{ _p.web }}web/app_dev.php/contact">{{ "Contact"|get_lang }}</a></li>
                {% endif %}
            </ul>
           {% if _u.logged == 1 %}
           <ul class="nav navbar-nav navbar-right">
               {% if user_notifications is not null %}
               <li><a href="{{ message_url }}">{{ user_notifications }}</a></li>
               {% endif %}
               {% if _u.status != 6 %}
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                        <img class="img-circle" src="{{ _u.avatar_small }}" alt="{{ _u.complete_name }}" />  <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu" role="menu">
                        {% if _u.is_admin == 1 %}
                            <li class=""><a href="{{ _p.web }}main/admin">{{ "NomPageAdmin"|get_lang }}</a></li>
                            {% endif %}
                            <li class=""><a href="{{ _p.web }}main/calendar/agenda_js.php?type=personal">{{ "AllowPersonalAgendaTitle"|get_lang }}</a></li>
                            <li role="separator" class="divider"></li>
                        <li>
                            {{ profile_link }}
                            {{ message_link }}
                            {{ certificate_link }}
                        </li>
                    </ul>
                </li>
               {% if logout_link is not null %}
               <li>
                   <a id="logout_button" title="{{ "Logout"|get_lang }}" href="{{ logout_link }}" >
                       <em class="fa fa-sign-out"></em> {{ "Logout"|get_lang }}
                   </a>
               </li>
               {% endif %}
               {% endif %}
            </ul>
            {% endif %}
        </div><!-- /.navbar-collapse -->
    </nav>
</div><!-- /.container-fluid -->
