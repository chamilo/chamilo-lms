<?php
/**
 * @package chamilo.plugin.before_login
 */
if (api_is_anonymous()) {
    // Only available in the index.php page
    $loginAccepted = isset($_SESSION['before_login_accepted']) ? $_SESSION['before_login_accepted'] : null;
    $parsedUrl = parse_url($_SERVER['REQUEST_URI']);
    $currentPage = str_replace('index.php', '', $parsedUrl['path']);

    if (api_get_path(REL_PATH) !== $currentPage) {
        return null;
    }

    // Hide only if the before_login_accepted session was set to ON.
    if ($loginAccepted) {
        return null;
    }

    // Only available for the selected language.
    $languageToActivate = api_get_plugin_setting('before_login', 'language');

    if (api_get_interface_language() != $languageToActivate) {
        return null;
    }

    $option1 = api_get_plugin_setting('before_login', 'option1');
    $urlOption1 = api_get_plugin_setting('before_login', 'option1_url');

    $option2 = api_get_plugin_setting('before_login', 'option2');
    $urlOption2 = api_get_plugin_setting('before_login', 'option2_url');

    $form = new FormValidator('form');

    $renderer = &$form->defaultRenderer();
    $renderer->setFormTemplate('<form{attributes}><table border="0" cellpadding="5" cellspacing="0" width="100%">{content}</table></form>');
    $renderersetCustomElementTemplate->setCustomElementTemplate('<tr><td>{element}</td></tr>');

    $form->addElement('html', $option1);
    $form->addElement('checkbox', 'left', null, get_lang('Yes'));
    $form->addElement('button', 'submit', get_lang('Confirm'), ['class' => 'btn btn-primary']);
    $formHtml = $form->returnForm();
    if ($form->validate()) {
        $result = $form->getSubmitValues();
        if (isset($result['left']) && $result['left']) {
            $_SESSION['before_login_accepted'] = 1;
            header('Location: '.$urlOption1);
            exit;
        }
    }

    $form2 = new FormValidator('form');

    if (!empty($option2) && !empty($urlOption2)) {
        $renderer = &$form2->defaultRenderer();
        $renderer->setHeaderTemplate('');
        $renderer->setFormTemplate('<form{attributes}><table border="0" cellpadding="5" cellspacing="0" width="100%">{content}</table></form>');
        $renderer->setCustomElementTemplate('<tr><td>{element}</td></tr>');

        $form2->addElement('html', $option2);
        $form2->addElement('checkbox', 'right', null, get_lang('Yes'));
        $form2->addElement('button', 'submit', get_lang('Send'));
        $formHtml2 = $form2->returnForm();

        if ($form2->validate()) {
            $result = $form2->getSubmitValues();
            if (isset($result['right']) && $result['right']) {
                header('Location: '.$urlOption2);
                exit;
            }
        }
    }

    $_template['form_option1'] = $formHtml;
    $_template['form_option2'] = $formHtml2;
}
