
<nav id="menubar" class="navbar navbar-default">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#menuone"
                    aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="{{ _p.web }}">{{ _s.site_name }}</a>
        </div>
        <div class="collapse navbar-collapse" id="menuone">
            <ul class="nav navbar-nav">
                {% for item in menu %}
                    <li class="{{ item.key }} {{ item.current }}">
                        <a href="{{ item.url }}" {{ item.target ? 'target="' ~ item.target ~ '"' : '' }} title="{{ item.title }}">
                            {{ item.title }}
                        </a>
                    </li>
                {% endfor %}
            </ul>
            {% if _u.logged == 1 %}
                <script>
                    $(document).ready(function () {
                        $.get('{{ _p.web_main }}inc/ajax/message.ajax.php?a=get_count_message', function(data){
                            var message = data;
                            if( message === "0"){
                                $("#count_message_li").addClass('hidden');
                            } else {
                                $("#count_message_li").removeClass('hidden');
                                $("#count_message").append(message);
                            }
                        });
                    });
                </script>
                
                <ul class="nav navbar-nav navbar-right">
                    <li id="count_message_li" class="hidden">
                        <a href="{{ message_url }}">
                            <span id="count_message" class="badge badge-warning"></span>
                        </a>
                    </li>
                    {% if _u.status != 6 %}
                        <li class="dropdown avatar-user">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                               aria-expanded="false">
                                
                                <img class="img-circle" src="{{ _u.avatar_small }}" alt="{{ _u.complete_name }}"/>
                                <span class="username-movil">{{ _u.complete_name }}</span>
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu" role="menu">
                                <li class="user-header">
                                    <div class="text-center">
                                        <img class="img-circle" src="{{ _u.avatar_medium }}"
                                             alt="{{ _u.complete_name }}"/>
                                        <p class="name"><a href="{{ profile_url }}">{{ _u.complete_name }}</a></p>
                                        <p><i class="fa fa-envelope-o" aria-hidden="true"></i> {{ _u.email }}</p>
                                    </div>
                                </li>
                                <li role="separator" class="divider"></li>
                                <li class="user-body">
                                    <a title="{{ "Inbox"|get_lang }}" href="{{ message_url }}">
                                        <em class="fa fa-envelope" aria-hidden="true"></em> {{ "Inbox"|get_lang }}
                                    </a>
                                </li>
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
                </ul>
            {% endif %}
        </div>
    </div>
</nav>
