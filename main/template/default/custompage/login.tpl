<div class="custompage">
    <div class="limiter">
        <div class="container-login">
            <div class="wrap-login width-login">
                <form class="login100-form validate-form" action="{{ _p.web }}" method="post">
                    <div class="logo">
                        <img width="250px" class="img-responsive" title="{{ _s.site_name }}" src="{{ _p.web_css_theme }}images/header-logo.svg">
                    </div>
                    <h3 class="title">{{ 'Login'|get_lang() }}</h3>
                    {{ mgs_flash }}
                    {% if error %}
                    <div class="alert alert-warning" role="alert">
                        {{ error }}
                    </div>
                    {% endif %}
                    <div class="form-group">
                        <label for="user">{{ 'LoginOrEmailAddress'|get_lang() }}</label>
                        <input type="text" class="form-control" id="user" name="login" ">
                    </div>
                    <div class="form-group">
                        <label for="password">{{ 'Password'|get_lang() }}</label>
                        <input type="password" class="form-control" name="password" id="password" >
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        {{ 'LoginEnter'|get_lang() }}
                    </button>
                    {% if url_register %}
                    <a href="{{ url_register }}" class="btn btn-success btn-block" >
                        {{ 'Registration'|get_lang() }}
                    </a >
                    {% endif %}
                    <div class="last-password">
                        <a href="{{ url_lost_password }}">
                            {{ 'LostPassword'|get_lang() }}
                        </a>
                    </div>
                </form>
                <div class="software-name">
                    <a href="{{_p.web}}" target="_blank">
                        {{ "PoweredByX" |get_lang | format(_s.software_name) }}
                    </a>&copy; {{ "now"|date("Y") }}
                </div>
            </div>
        </div>
    </div>
</div>

