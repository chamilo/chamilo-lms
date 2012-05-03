{#
    This is a Chamilo plugin using Twig you can use these handy shorcuts like:
    
    1. Shortcuts 
    
    _p = url chamilo paths
    _u = user information of the current user
    
    2. i18n
    
    You can use i18n variables just use this syntax:
    
    {{"HelloWorld"|get_lang}}
    
    Now you can add your variables in the main/lang/english/ or main/lang/spanish/ for example in spanish:
    $HelloWorld = "Hola Mundo";
    
    3. Portal settings    
        You can access the portal settings using:
        {{"siteName"|get_setting}}
        For more settings check the settings_current database table
    4. Read more
        You can also see more examples in main/template/default/layout
#}

{% if show_user_info.show_message is not null and _u.logged == 1 %}
    <div class="well">
        {{ "WelcomToChamiloUserX" | get_lang | format(show_user_info.user_info.complete_name) }}            
    </div>
{% endif %}