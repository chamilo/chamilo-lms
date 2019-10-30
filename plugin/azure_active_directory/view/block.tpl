{% if not _u.logged %}
    <div id="azure-active-directory-login">
        {% if not azure_active_directory.block_title is empty %}
            <h4>{{ azure_active_directory.block_title }}</h4>
        {% endif %}

        {% if not azure_active_directory.signin_url is empty %}
            <a href="{{ azure_active_directory.signin_url }}" class="btn btn-default">{{ 'SignIn'|get_lang }}</a>
        {% endif %}

        {% if not azure_active_directory.signout_url is empty %}
            <a href="{{ azure_active_directory.signout_url }}" class="btn btn-danger">{{ 'Logout'|get_lang }}</a>
        {% endif %}

        {% if azure_active_directory.management_login_enabled %}
            <hr>
            <a href="{{ _p.web_plugin  ~ 'azure_active_directory/login.php' }}">
                {{ azure_active_directory.management_login_name }}
            </a>
        {% endif %}
    </div>
{% endif %}
