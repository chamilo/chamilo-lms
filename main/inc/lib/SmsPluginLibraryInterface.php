<?php
/* For licensing terms, see /license.txt */

/**
 * Class SmsPluginLibraryInterface
 *
 * @author Julio Montoya
 *
 */
interface SmsPluginLibraryInterface
{
    /**
     * getMobilePhoneNumberById (retrieves a user mobile phone number by user id)
     *
     * @param int $userId
     *
     * @return  int User's mobile phone number
     */
    public function getMobilePhoneNumberById($userId);

    /**
     * @param array $additionalParameters
     * @return mixed
     */
    public function send($additionalParameters);

    /**
     * @param array $additionalParameters
     * @return mixed
     */
    public function getSms($additionalParameters);

    /**
     * buildSms (builds an SMS from a template and data)
     * @param   object  ClockworksmsPlugin object
     * @param   object  Template object
     * @param   string  Template file name
     * @param   string  Text key from lang file
     * @param   array   Data to fill message variables (if any)
     * @return  object  Template object with message property updated
     */
    public function buildSms($plugin, $tpl, $templateName, $messageKey, $parameters = null);
}
