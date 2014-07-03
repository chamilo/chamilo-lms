
    {% if ("use_virtual_keyboard" | get_setting) == 'true' %}
        <link href="{{ _p.web_library_js_path }}keyboard/keyboard.css" rel="stylesheet" type="text/css" />
        <script src="{{ _p.web_library_js_path }}keyboard/jquery.keyboard.js" type="text/javascript" language="javascript"></script>
        <script>
            $(function(){
                $('.virtualkey').keyboard({
                    layout:'custom',
                    customLayout: {
                        'default': [
                            '1 2 3 4 5 6 7 8 9 0 {bksp}',
                            'q w e r t y u i o p',
                            'a s d f g h j k l',
                            'z x c v b n m',
                            '{cancel} {accept}'
                        ]
                    }
                });
            });
        </script>
    {% endif %}

    <form class="form-signin" action="{{ url('secured_login_check') }}" method="post">
        <h2 class="form-signin-heading">{{ 'SignIn' | trans }}</h2>
        {% if error %}
            <div class="alert">
                {{ error|trans }}
            </div>
        {% endif %}

        <input class="form-control virtualkey" type="text" name="username" placeholder="{{ 'Username' | trans }}"/>
        <input class="form-control virtualkey" type="password" name="password" placeholder="{{ 'Password' | trans }}" />
        <button class="btn btn-lg btn-primary btn-block" type="submit">{{ 'LoginEnter' | trans }}</button>
    </form>
