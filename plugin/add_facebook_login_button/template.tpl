{#
    This is a Chamilo plugin using Smarty you can use handy shorcuts like:
    
    1. Shortcuts 
    
    $_p = url chamilo paths
    $_u = user information of the current user
    
    2. i18n
    
    You can use i18n variables just use this syntax:
    
    {"HelloWorld"|get_lang}
    
    Now you can add your variables in the main/lang/english/ or main/lang/spanish/ for example in spanish:    
    $HelloWorld = "Hola Mundo";
    
    3. Portal settings
    
        You can access the portal settings using:
        {"siteName"|api_get_setting}
        For more settings check the settings_current database
        
    4. Read more
        You can also see more examples in the the main/template/default/layout files
        
    5. {$_p|var_dump} pour les path {$_u|var_dump} pour info de  l'utilisateur loggé
#}


{% if add_facebook_login_button.show_message %}
    <div class="well">
        <a href="{{add_facebook_login_button.facebook_href_link}}">
            <img src="{{add_facebook_login_button.facebook_button_url}}"/>
        </a>
    </div>
{% endif %}
