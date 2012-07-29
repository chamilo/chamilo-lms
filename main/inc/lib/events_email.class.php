<?php

/**
 * 
 * Manages the email sending action when a event requires it.
 *
 */
class EventsMail 
{

    /**
     * Sends email according to an event
     *
     * @param string $event_name the name of the event that was triggered
     * @param array $event_data what to put in the mail
     * 
     * Possible key :
     * - $event_data["about_user"] (= $user_id)
     * - $event_data["prior_lang"]
     * 
     * Warning :
     * - $event_data["send_to"] MUST BE an array
     */
    public static function send_mail($event_name, $event_data) 
    {
        /**
         * Global explanation :
         * 1. we get information about the user that fired the event (in $event_data["about_user"])
         * 2. we send mail to people that are in the $event_data["send_to"]
         * 2b. if a language was specified, we use that one to send the mail, else we get the user's language, if there isn't any, we get the english one
         * 3. we do the same with the people associated to the event through the admin panel 
         */
        global $event_config;

        // common variable for every mail sent
        $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
        $email_admin = api_get_setting('emailAdministrator');
        // basic  keys
        $event_data["sitename"] = api_get_setting('siteName');
        $event_data["administrator_name"] = api_get_setting('administratorName');
        $event_data["administrator_surname"] = api_get_setting('administratorSurname');
        $event_data["administrator_phone"] = api_get_setting('administratorTelephone');
        $event_data["administrator_email"] = api_get_setting('emailAdministrator');
        $event_data["portal"] = api_get_path(WEB_PATH);
        
        // Fill the array's cells with info regarding the user that fired the event
        // (for the keys in the template)
        if ( isset($event_data["about_user"]) ) 
        {
            $about_user = api_get_user_info($event_data["about_user"]);
            $event_data["firstname"] = $about_user["firstname"];
            $event_data["lastname"] = $about_user["lastname"];
            $event_data["username"] = $about_user["username"];
            $event_data["usermail"] = $about_user["mail"];
            $event_data["language"] = $about_user["language"];
            $event_data["user_id"] = $about_user["user_id"];
        }

        // First, we send the mail to people we put in the $event_data["send_to"] ========================================================
        if ($event_data["send_to"] != null) // the users we precised need to receive the mail 
        {
            foreach ($event_data["send_to"] as $id) // for every member put in the array 
            {
                // get user's info (to know where to send)
                $user_info = api_get_user_info($id);
                
                // get the language the email will be in
                if ($event_data["prior_lang"] != null) // if $lang is not null, we use that lang
                { 
                    $language = $event_data["prior_lang"];
                }
                else  // else we use the user's language
                {
                    $sql = 'SELECT language FROM ' . Database::get_main_table(TABLE_MAIN_USER) . ' u 
                    WHERE u.user_id = "' . $id . '"
                    ';
                    $language = Database::store_result(Database::query($sql), 'ASSOC');
                    $language = $language[0]["language"];
                }
                
                // we get the message in the correct language (or in english if doesn't exist)
                $result = self::getMessage($event_name, $language);
                $message = "";
                $subject = "";
                self::getCorrectMessage($message, $subject, $language, $result);
                
                // replace the keycodes used in the message
                self::formatMessage($message, $subject, $event_config, $event_name, $event_data);
                
                // sending email                    
                $recipient_name = api_get_person_name($user_info['firstname'], $user_info['lastname']);

                // checks if there's a file we need to join to the mail
                if (isset($values["certificate_pdf_file"]))
                {
                    $message = str_replace("\n", "<br />", $message);                  
                    @api_mail_html($recipient_name, $user_info["mail"], $subject, $message, $sender_name, $email_admin, null, array($values['certificate_pdf_file']));
                }
                else
                {
                    @api_mail($recipient_name, $user_info["mail"], $subject, $message, $sender_name, $email_admin);
                }
                
                // If the mail only need to be send once (we know that thanks to the events.conf), we log it in the table
                if ($event_config[$event_name]["sending_mail_once"])
                {
                    $sql = 'INSERT INTO ' . Database::get_main_table(TABLE_EVENT_SENT) . ' 
                        (user_from, user_to, event_type_name)
                        VALUES ('.$event_data["user_id"].', '.$id.' ,"'.Database::escape_string($event_name).'");
                        ';
                    Database::query($sql);
                }
            }
        }
        
        // Second, we send to people linked to the event
        // So, we get everyone
        $sql = 'SELECT u.user_id, u.language, u.email, u.firstname, u.lastname FROM ' . Database::get_main_table(TABLE_EVENT_TYPE_REL_USER) . ' ue 
                INNER JOIN '.Database::get_main_table(TABLE_MAIN_USER).' u ON u.user_id = ue.user_id
                WHERE event_type_name = "' . $event_name . '"';
        $result = Database::store_result(Database::query($sql), 'ASSOC');
        
        foreach ($result as $key => $value) // for each of the linked users
        {
            // we get the language
            if ($event_data["prior_lang"] != null) // if $lang is not null, we use that lang 
            { 
                $language = $event_data["prior_lang"];
            }
            else // else we get the user's lang
            { 
                $sql = 'SELECT language FROM '.Database::get_main_table(TABLE_MAIN_USER).' 
                    where user_id = '.$value["user_id"].' ';
                $result = Database::store_result(Database::query($sql), 'ASSOC');

                $language = $result[0]["language"];
            }
            
            // we get the message in the correct language (or in english if doesn't exist)
            $result = self::getMessage($event_name, $language);
            $message = "";
            $subject = "";
            self::getCorrectMessage($message, $subject, $language, $result);
               
            // replace the keycodes used in the message
            self::formatMessage($message, $subject, $event_config, $event_name, $event_data);
            
            // we send the mail
            $recipient_name = api_get_person_name($value['firstname'], $value['lastname']);

            @api_mail($recipient_name, $value["email"], $subject, $message, $sender_name, $email_admin);
            
            // If the mail only need to be send once (we know that thanks to the events.conf, we log it in the table
            if ($event_config[$event_name]["sending_mail_once"])
            {
                $sql = 'INSERT INTO ' . Database::get_main_table(TABLE_EVENT_SENT) . ' 
                    (user_from, user_to, event_type_name)
                    VALUES ('.$event_data["user_id"].', '.$value["user_id"].' , "'.Database::escape_string($event_name).'");
                    ';
                Database::query($sql);
            }
        }
    }

    /**
     * Checks if a message in a language exists, if the event is activated 
     * and if "manage event" is checked in admin panel.
     * If yes to three, we can use this class, else we still use api_mail. 
     *
     * @param string $event_name
     * @return boolean 
     */
    public static function check_if_using_class($event_name) {
        if (api_get_setting('activate_email_template') === 'false') {
            return false;
        }
        $current_language = api_get_interface_language();

        $sql = 'SELECT COUNT(*) as total FROM ' . Database::get_main_table(TABLE_EVENT_EMAIL_TEMPLATE) . ' em 
        INNER JOIN ' . Database::get_main_table(TABLE_MAIN_LANGUAGE) . ' l on em.language_id = l.id
        WHERE em.event_type_name = "' . $event_name . '" and l.dokeos_folder = "'.$current_language.'" and em.activated = 1';

        $exists = Database::store_result(Database::query($sql), 'ASSOC');                
        if ($exists[0]["total"])  {
            return true;            
        } else {
            return false;
        }
    }
    
    /**
     * Get the record containing the good message and subject
     *
     * @param string $event_name
     * @param string $language
     * @return array 
     */
    private static function getMessage($event_name, $language)
    {
        $sql = 'SELECT message, subject, l.dokeos_folder FROM ' . Database::get_main_table(TABLE_EVENT_EMAIL_TEMPLATE) . ' em 
                    INNER JOIN ' . Database::get_main_table(TABLE_MAIN_LANGUAGE) . ' l on em.language_id = l.id
                    WHERE em.event_type_name = "' . $event_name . '" and (l.dokeos_folder = "' . $language . '" OR l.dokeos_folder = "english") and em.message <> ""
                    ';
        return Database::store_result(Database::query($sql), 'ASSOC');
    }
    
    /**
     * Get the correct message, meaning in the specified language or in english if previous one doesn't exist
     *
     * @param string $message
     * @param string $subject
     * @param string $language
     * @param array $result 
     */
    private static function getCorrectMessage(&$message, &$subject, $language, $result)
    {
        foreach ($result as $msg) 
        {
            if ($msg["dokeos_folder"] == $language) 
            {
                $message = $msg["message"];
                $subject = $msg["subject"];
                break;
            } 
            else if ($msg["dokeos_folder"] == "english") 
            {
                $message = $msg["message"];
                $subject = $msg["subject"];
            }
        }
    }
    
    /**
     * Replaces the ((key)) by the good values
     *
     * @param string $message
     * @param string $subject
     * @param array $event_config
     * @param string $event_name 
     */
    private static function formatMessage(&$message, &$subject, $event_config, $event_name, &$event_data)
    {
        foreach ($event_config[$event_name]["available_keyvars"] as $key => $word) 
        {
            $message = str_replace('((' . $key . '))', $event_data[$word], $message);
            $subject = str_replace('((' . $key . '))', $event_data[$word], $subject);
        }
    }
}
