<?php

//A user must be logged in
$_template['show_message']   = false;

if (!api_is_anonymous()) {
    $_template['show_message']   = true;
    
    //Getting the current user id
    $user_id = api_get_user_id();    
    
    //Getting the current user info
    $user_info = api_get_user_info($user_id);
            
    //You can also use smarty setting variables in the special variable called template
    $_template['user_info']   = $user_info;
    $_template['username']    = $user_info['username'];
}