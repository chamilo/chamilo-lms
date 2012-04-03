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
#}
{% if show_user_info.show_message is not null %}
<div class="well">
    {{"Welcome"|get_lang}} {{show_user_info.user_info.complete_name}} ({{show_user_info.username})
    <br />
    The administrator - {{"siteName"|get_setting}}
</div>
{% endif %}