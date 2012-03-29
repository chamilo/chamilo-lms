{* 
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
*}


{if $add_cas_login_button.show_message}
    <link href="{$_p.web_plugin}/add_cas_login_button/css.css" rel="stylesheet" type="text/css"> 
    <div class="well">
        {if $add_cas_login_button.url_label}
            <img src="{$add_cas_login_button.url_label}" class='cas_plugin_image'/>
        {/if}
        <h4>{$add_cas_login_button.button_label}</h4>
        {if $add_cas_login_button.url_label}
            <div class='cas_plugin_clear'>&nbsp;</div>
        {/if}
        <div class='cas_plugin_comm'>{$add_cas_login_button.comm_label}</div>
        <button class="btn" onclick="javascript:self.location.href='main/auth/cas/logincas.php'">{"LoginEnter"|get_lang}</button>    
    </div>
{/if}
