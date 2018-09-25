<div id="navigation" class="notification-panel">
    {{ help_content }}
    {{ bug_notification }}
</div>
{% block topbar %}
    {% include 'layout/topbar.tpl'|get_template %}
{% endblock %}

{% if header_extra_content is not null %}
    {{ header_extra_content }}
{% endif %}

    <div class="container">
        <div class="row">
            <div class="col-md-3 col-xs-4">
                <div class="logo">
                    {{ logo }}
                </div>
            </div>
            <div class="col-md-6 col-xs-4">
                <div class="row">
                    <div class="col-sm-4">
                        {% if plugin_header_left is not null %}
                            <div id="plugin_header_left">
                                {{ plugin_header_left }}
                            </div>
                        {% endif %}
                    </div>
                    <div class="col-sm-4">
                        {% if plugin_header_center is not null %}
                            <div id="plugin_header_center">
                                {{ plugin_header_center }}
                            </div>
                        {% endif %}
                    </div>
                    <div class="col-sm-4">
                        {% if plugin_header_right is not null %}
                            <div id="plugin_header_right">
                                {{ plugin_header_right }}
                            </div>
                        {% endif %}
                        <script>
                            $(document).on('ready', function () {
                                $("#notifications").load("{{ _p.web_main }}inc/ajax/online.ajax.php?a=get_users_online");
                            });
                        </script>
                        <div class="section-notifications">
                            <ul id="notifications" class="nav nav-pills pull-right">
                            </ul>
                        </div>
                        {{ accessibility }}
                    </div>
                </div>
            </div>
             <div class="col-md-3 col-xs-4">
                 <div class="logo-ofaj pull-right">
                    <a href="#"><img class="img-responsive" src="{{ _p.web_css_theme }}images/logo-ofaj.png"/></a>
                </div>
            </div>
        </div>
    </div>
</header>
{% block menu %}
    {% include 'layout/menu.tpl'|get_template %}
{% endblock %}
{% include 'layout/course_navigation.tpl'|get_template %}
