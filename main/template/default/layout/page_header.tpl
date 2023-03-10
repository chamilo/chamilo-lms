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
        {% if plugin_header_left_logo is not null %}
        <div class="col-xs-2 col-md-1">
            <div id="plugin_header_left_logo" class="">
                {{ plugin_header_left_logo }}
            </div>
        </div>
        {% endif %}
        <div class="col-xs-10 col-md-3">
            {% if _u.logged != 1 %}
                <div class="key-login">
                    <a href="#login-block" id="btn-login" class="btn btn-default">
                        <img src="{{ 'key.png'|icon(22) }}" alt="key"> {{ 'LoginAsThisUser'|get_lang }}
                    </a>
                </div>
            {% endif %}
            <div class="logo">
                {{ logo }}
            </div>
        </div>
        <div class="col-xs-12 col-md-8">
            <div class="row">
                <div class="col-sm-4">
                    {% if plugin_header_left is not null %}
                        <div id="plugin_header_left">
                            {{ plugin_header_left }}
                        </div>
                    {% endif %}
                </div>
                <div class="col-sm-3">
                    {% if plugin_header_center is not null %}
                        <div id="plugin_header_center">
                            {{ plugin_header_center }}
                        </div>
                    {% endif %}
                </div>
                <div class="col-sm-5">
                    <ol class="header-ol">
                        {% if plugin_header_right is not null %}
                            <li>
                                <div id="plugin_header_right">
                                    {{ plugin_header_right }}
                                </div>
                            </li>
                        {% endif %}
                        <li>
                            <div class="section-notifications">
                                {% if _u.logged == 1 and not user_in_anon_survey %}
                                    <ul id="notifications" class="nav nav-pills pull-right">
                                    </ul>
                                {% endif %}
                            </div>
                        </li>
                        <li>
                            {{ accessibility }}
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

{% block menu %}
{% include 'layout/menu.tpl'|get_template %}
{% endblock %}

{% include 'layout/course_navigation.tpl'|get_template %}
