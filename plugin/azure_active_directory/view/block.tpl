{% if not _u.logged %}
    <div id="azure-active-directory-login">
        {% if not azure_active_directory.block_title is empty %}
            <h4>{{ azure_active_directory.block_title }}</h4>
        {% endif %}

        {% if not azure_active_directory.signin_url is empty %}
            <a href="{{ azure_active_directory.signin_url }}" class="btn btn-default">{{ 'SignIn'|get_lang }}</a>
        {% endif %}

        {% if not azure_active_directory.signup_url is empty %}
            <a href="{{ azure_active_directory.signup_url }}" class="btn btn-success">{{ 'SignUp'|get_lang }}</a>
        {% endif %}

        {% if not azure_active_directory.signunified_url is empty %}
            <a href="{{ azure_active_directory.signunified_url }}" class="btn btn-info">{{ 'SignUpAndSignIn'|get_plugin_lang('AzureActiveDirectory') }}</a>
        {% endif %}
    </div>
{% endif %}
