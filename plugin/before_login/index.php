<?php
/**
 * @package chamilo.plugin.before_login
 */

if (api_is_anonymous()) {

    // Only available in the index.php page
    $loginAccepted = isset($_SESSION['before_login_accepted']) ? $_SESSION['before_login_accepted'] : null;
    $currentPage = str_replace('index.php', '', $_SERVER['REQUEST_URI']);
    //var_dump(api_get_path(REL_PATH), $currentPage);
    if (api_get_path(REL_PATH) !== $currentPage) {
        return null;
    }

    if ($loginAccepted) {
        return null;
    }

    $option1 = api_get_plugin_setting('before_login', 'option1');
    $urlOption1 = api_get_plugin_setting('before_login', 'option1_url');

    $option2 = api_get_plugin_setting('before_login', 'option2');
    $urlOption2 = api_get_plugin_setting('before_login', 'option2_url');

    $form = new FormValidator('form');

    $renderer =& $form->defaultRenderer();
    $renderer->setHeaderTemplate('');
    $renderer->setFormTemplate('<form{attributes}><table border="0" cellpadding="5" cellspacing="0" width="100%">{content}</table></form>');
    $renderer->setElementTemplate('<tr><td>{element}</td></tr>');

    $form->addElement('header', $option1);
    $form->addElement('checkbox', 'left', null, get_lang('Yes'));
    $form->addElement('button', 'submit', get_lang('Send'));

    if ($form->validate()) {
        $result = $form->getSubmitValues();
        if (isset($result['left']) && $result['left']) {
            $_SESSION['before_login_accepted'] = 1;
            header('Location: '.$urlOption1);
            exit;
        }
    }

    $form2 = new FormValidator('form');

    $renderer =& $form2->defaultRenderer();
    $renderer->setHeaderTemplate('');
    $renderer->setFormTemplate('<form{attributes}><table border="0" cellpadding="5" cellspacing="0" width="100%">{content}</table></form>');
    $renderer->setElementTemplate('<tr><td>{element}</td></tr>');

    $form->addElement('header', $option2);
    $form2->addElement('checkbox', 'right', null, get_lang('Yes'));
    $form2->addElement('button', 'submit', get_lang('Send'));

    if ($form2->validate()) {
        $result = $form2->getSubmitValues();
        if (isset($result['right']) && $result['right']) {
            header('Location: '.$urlOption2);
            exit;
        }
    }

    $_template['option1'] = api_get_plugin_setting('before_login', 'option1');
    $_template['option2'] = api_get_plugin_setting('before_login', 'option2');
    $_template['form_option1'] = $form->return_form();
    $_template['form_option2'] = $form2->return_form();
}