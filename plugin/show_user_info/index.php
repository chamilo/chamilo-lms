<?php

//A user must be logged in
if (!api_is_anonymous()) {
    //Getting the current user id
    $user_id = api_get_user_id();    
    
    //Getting the current user info
    $user_info = api_get_user_info($user_id);
    
    //Showing the complete name (that means the firstname and lastname depending of the language)
    echo 'This is a simple echo';
    
    //If you want to see more data you can do a var_dump($user_info);    
    
    //You can also change the style
    echo '<h4>This is an echo with style :)</h4>';    
        
    //You can also use smarty setting variables in the special variable called template
    $_template['user_info'] = $user_info;
    $_template['user_email'] = $user_info['mail'];
}