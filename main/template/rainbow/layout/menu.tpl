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
                    {% for items in menu.possible_tabs %}     
                        {% if items.key != 'profile' and items.key != 'dashboard' and items.key != 'my-space' and items.key != 'admin' %}
                        {% set counter = counter + 1 %}
                            <li class="item-menu menu-{{ counter }} {{ items.key }} {{ items.current }}"><a href="{{ items.url }}">{{ items.title }}</a></li>
                        {% endif %}
                    {% endfor %}
                {% endif %}
                {% if _u.logged == 0 %}
                    <li class="item-menu menu-1"><a href="{{ _p.web }}">{{ "CampusHomepage"|get_lang }}</a></li>
                {% endif %}
                    <li class="item-menu menu-2"><a href="{{ _p.web }}web/app_dev.php/faq">{{ "FAQ"|get_lang }}</a></li>
                <!-- <li class="item-menu menu-2 dropdown">
                    <a href="{{ _p.web }}web/app_dev.php/faq" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                        {{ "FAQ"|get_lang }}
                        <b class="caret"></b>
                    </a>
                    <ul class="dropdown-menu menu_level_1" role="menu">
                     {% for category in faq_categories %}
                         <li divider_append="divider_append" class="first last">
                            <a href="{{ _p.web }}web/app_dev.php/faq/{{ category.slug }}">
                                {{ category.headline }}
                            </a>
                         </li>
                    {% endfor %}
                    </ul> 
                </li> -->
                {% if _u.logged == 0 %}
                    <li class="item-menu menu-3"><a href="{{ _p.web }}main/auth/inscription.php">{{ "Subscription"|get_lang }}</a></li>
                    <li class="item-menu menu-4"><a href="#">{{ "Demo"|get_lang }}</a></li>
                    <li class="item-menu menu-5"><a href="#">{{ "Contact"|get_lang }}</a></li>
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
                        {{ _u.complete_name }} <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu" role="menu">
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
