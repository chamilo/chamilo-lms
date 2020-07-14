<?php
/**
 * This script is a configuration file for the date plugin. You can use it as a master for other platform plugins (course plugins are slightly different).
 * These settings will be used in the administration interface for plugins (Chamilo configuration settings->Plugins).
 *
 * @package chamilo.plugin
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Plugin details (must be present).
 */

//the plugin title
$plugin_info['title'] = 'Enable or disable logout button';

//the comments that go with the plugin
$plugin_info['comment'] = "If you use some other authentication than local Chamilo authentication, you may have to disable the Chamilo logout button and give users information about your SSO logout.";
//the plugin version
$plugin_info['version'] = '1.0';
//the plugin author
$plugin_info['author'] = 'Hubert Borderiou';
//the plugin configuration
$form = new FormValidator('eaclbb_form');
$form->addElement("html", "<div class='normal-message'>Fill the text boxes below to overwrite the default values used in this plugin</div>");
$form->addElement('text', 'eaclbb_form_link_url', 'Page to load when clicking on the logout button');
$form->addElement('text', 'eaclbb_form_link_infobulle', 'Tooltip text for the logout button (displayed on mouseover)');
$form->addElement('checkbox', 'eaclbb_form_link_image', "Display the logout button disabled (black and white)", "", ['checked' => true]);
$form->addElement('checkbox', 'eaclbb_form_alert_onoff', "Display an alert when clicking on the logout button", "", ['checked' => true]);
$form->addElement('text', 'eaclbb_form_alert_text', "Text displayed in the alert box when clickng on the logout button (if checkbox above has been checked).");

$form->addButtonSave(get_lang('Save'), 'submit_button');
//get default value for form
$tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_url = api_get_setting('ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_url');
$tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_infobulle = api_get_setting('ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_infobulle');
$tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_image = api_get_setting('ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_image');
$tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_alert_onoff = api_get_setting('ext_auth_chamilo_logout_button_behaviour_eaclbb_form_alert_onoff');
$tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_alert_text = api_get_setting('ext_auth_chamilo_logout_button_behaviour_eaclbb_form_alert_text');
if ($tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_url) {
    $defaults['eaclbb_form_link_url'] = $tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_url['eaclbb_form_link_url'];
}

if ($tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_infobulle) {
    $defaults['eaclbb_form_link_infobulle'] = $tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_infobulle['eaclbb_form_link_infobulle'];
}

if ($tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_image) {
    $defaults['eaclbb_form_link_image'] = $tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_image['eaclbb_form_link_image'];
}
if ($tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_alert_onoff) {
    $defaults['eaclbb_form_alert_onoff'] = $tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_alert_onoff['eaclbb_form_alert_onoff'];
}
if ($tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_alert_text) {
    $defaults['eaclbb_form_alert_text'] = $tab_default_ext_auth_chamilo_logout_button_behaviour_eaclbb_form_alert_text['eaclbb_form_alert_text'];
}
$form->setDefaults($defaults);
//display form
$plugin_info['settings_form'] = $form;

// Set the templates that are going to be used
$plugin_info['templates'] = ['template.tpl'];
