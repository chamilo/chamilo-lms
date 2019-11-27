<?php
// personalize the logout button behaviour
global $_user;
$_template['show_message'] = false;

if (!api_is_anonymous() &&
    api_get_setting('cas_activate') == 'true' &&
    $_user['auth_source'] == CAS_AUTH_SOURCE
) {
    $_template['show_message'] = true;
    // the link URL
    $link_url = "#";
    if (!empty($plugin_info['settings']['ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_url'])) {
        $link_url = api_htmlentities($plugin_info['settings']['ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_url']);
    }
    // the infobulle
    $link_infobulle = "Vous devez fermer votre navigateur pour clore votre session de travail.";
    if (!empty($plugin_info['settings']['ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_infobulle'])) {
        $link_infobulle = api_htmlentities($plugin_info['settings']['ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_infobulle']);
    }
    // $link_image=1 if we replace the logout button by a grey one 'exit_na'
    $link_image = "1";
    if (empty($plugin_info['settings']['ext_auth_chamilo_logout_button_behaviour_eaclbb_form_link_image'])) {
        $link_image = "";
    }
    // checkbox to disaply an alert box when clicnkig on the logout button
    $alert_onoff = "1";
    if (empty($plugin_info['settings']['ext_auth_chamilo_logout_button_behaviour_eaclbb_form_alert_onoff'])) {
        $alert_onoff = "";
    }
    // alert text to display if check is on
    $alert_text = "\\\\nVous êtes connectés avec votre compte universitaire.\\\\n\\\\nVous devez *** fermer votre navigateur *** pour clore votre session de travail.";
    if (!empty($plugin_info['settings']['ext_auth_chamilo_logout_button_behaviour_eaclbb_form_alert_text'])) {
        $alert_text = $plugin_info['settings']['ext_auth_chamilo_logout_button_behaviour_eaclbb_form_alert_text'];
    }

    $_template['link_url'] = $link_url;
    $_template['link_infobulle'] = $link_infobulle;
    $_template['link_image'] = $link_image;
    $_template['alert_onoff'] = $alert_onoff;
    $_template['alert_text'] = $alert_text;
}
