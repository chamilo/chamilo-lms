{% extends app.template_style ~ "/layout/layout_1_col.tpl" %}
{% block content %}
<style type="text/css">
    body {
    padding-top: 40px;
    padding-bottom: 40px;
    background-color: #f5f5f5;
    }

    .form-signin {
    max-width: 300px;
    padding: 19px 29px 29px;
    margin: 0 auto 20px;
    background-color: #fff;
    border: 1px solid #e5e5e5;
    -webkit-border-radius: 5px;
       -moz-border-radius: 5px;
            border-radius: 5px;
    -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
       -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
            box-shadow: 0 1px 2px rgba(0,0,0,.05);
    }
    .form-signin .form-signin-heading,
    .form-signin .checkbox {
    margin-bottom: 10px;
    }
    .form-signin input[type="text"],
    .form-signin input[type="password"] {
    font-size: 16px;
    height: auto;
    margin-bottom: 15px;
    padding: 7px 9px;
    }
</style>
<form class="form-signin" action="{{ url('admin_login_check') }}" method="post">
    {{ error }}
    <input class="input-block-level" type="text" name="username" />
    <input class="input-block-level" type="password" name="password" value="" />
    <button class="btn btn-large btn-primary" type="submit">Sign in</button>
</form>

{% endblock %}
