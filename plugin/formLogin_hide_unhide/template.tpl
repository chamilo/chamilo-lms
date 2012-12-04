{% if formLogin_hide_unhide.show_message %}
    <link href="{{ _p.web_plugin }}formLogin_hide_unhide/css.css" rel="stylesheet" type="text/css">
    <div class="well">
        <a href="#" onclick="$('#formLogin').toggle(500)">{{ formLogin_hide_unhide.label }}</a>
    </div>
    <script type='text/javascript'>
        $(document).ready(function() {
            $('#formLogin').hide();
        });
    </script>
{% endif %}
