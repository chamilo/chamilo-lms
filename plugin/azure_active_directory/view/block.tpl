<div id="azure-active-directory-login">
    {% if _u.logged %}
        {# <a href="{{ azure_active_directory.signout_url }}" class="btn btn-primary">{{ 'Logout'|get_lang }}</a> #}
    {% else %}
        {% if not azure_active_directory.block_title is empty %}
            <h4>{{ azure_active_directory.block_title }}</h4>
        {% endif %}

        <a href="{{ azure_active_directory.signin_url }}" class="btn btn-default">{{ 'SignIn'|get_lang }}</a>
        <a href="{{ azure_active_directory.signup_url }}" class="btn btn-success">{{ 'SignUp'|get_lang }}</a>
    {% endif %}
</div>
