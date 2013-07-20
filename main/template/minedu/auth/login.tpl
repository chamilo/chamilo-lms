{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
<form class="form-signin" action="{{ url('admin_login_check') }}" method="post">
    <h2 class="form-signin-heading">{{ 'SignIn' | get_lang }}</h2>
    {{ error }}
    <input class="input-block-level" type="text" name="username" placeholder="{{ 'Username' | get_lang }}"/>
    <input class="input-block-level" type="password" name="password" placeholder="{{ 'Password' | get_lang }}" />
    <button class="btn btn-large btn-primary" type="submit">{{ 'LoginEnter' | get_lang }}</button>
</form>
{% endblock %}
