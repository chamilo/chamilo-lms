{% if _u.logged  == 0 %}
    {% if login_form %}
        <div id="login-block" class="panel panel-default">
            <div class="panel-body">
                {{ login_language_form }}
                {% if plugin_login_top is not null %}
                    <div id="plugin_login_top">
                        {{ plugin_login_top }}
                    </div>
                {% endif %}

                {{ login_failed }}

                {% set oauth2_plugin_enabled = 'oauth2'|api_get_plugin_setting('enable') %}
                {% set oauth2_plugin_manage_login = 'oauth2'|api_get_plugin_setting('manage_login_enable') %}

                {% if 'false' == oauth2_plugin_enabled or 'false' == oauth2_plugin_manage_login %}
                    {{ login_form }}

                    {% if "allow_lostpassword" | api_get_setting == 'true' or "allow_registration"|api_get_setting == 'true' %}
                        <ul class="nav nav-pills nav-stacked">
                            {% if "allow_registration"|api_get_setting != 'false' %}
                                <li><a href="{{ _p.web_main }}auth/inscription.php"> {{ 'SignUp'|get_lang }} </a></li>
                            {% endif %}

                            {% if "allow_lostpassword"|api_get_setting == 'true' %}
                                <li>
                                    <a href="{{ _p.web_main }}auth/lostPassword.php">{{ 'LostPassword'|get_lang }}</a>
                                </li>
                            {% endif %}
                        </ul>
                    {% endif %}

                {% endif %}

                {% if plugin_login_bottom is not null %}
                    <div id="plugin_login_bottom">
                        {{ plugin_login_bottom }}
                    </div>
                {% endif %}
            </div>
        </div>
    {% endif %}
{% endif %}
