{% if not _u.logged %}
    <div id="oauth2-login">
        {% if not oauth2.block_title is empty %}
            <h4>{{ oauth2.block_title }}</h4>
        {% endif %}

        {% if not oauth2.signin_url is empty %}
            <a href="{{ oauth2.signin_url }}" class="btn btn-default">{{ 'SignIn'|get_lang }}</a>
        {% endif %}

        {% if oauth2.management_login_enabled %}
            <hr>
            <a href="{{ _p.web_plugin  ~ 'oauth2/login.php' }}">
                {{ oauth2.management_login_name }}
            </a>
        {% endif %}
    </div>
{% endif %}
