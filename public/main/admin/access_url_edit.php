<?php

/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_global_admin_script();

if (!api_get_multiple_access_url()) {
    header('Location: index.php');
    exit;
}

$form = new FormValidator('add_url');

$form->addUrl('url', 'URL');
$form->addRule('url', get_lang('Required field'), 'required');
$form->addRule('url', '', 'maxlength', 254);
$form->addTextarea('description', get_lang('Description'));

// URL Images
$form->addFile('url_image_1', get_lang('Image'));
//$form->addElement('file', 'url_image_2', 'URL Image 2 (PNG)');
//$form->addElement('file', 'url_image_3', 'URL Image 3 (PNG)');

$defaults['url'] = 'http://';
$form->setDefaults($defaults);

$submit_name = get_lang('Add URL');
if (isset($_GET['url_id'])) {
    $url_id = (int) $_GET['url_id'];
    $num_url_id = UrlManager::url_id_exist($url_id);
    if (1 != $num_url_id) {
        header('Location: access_urls.php');
        exit();
    }
    $url_data = UrlManager::get_url_data_from_id($url_id);
    $form->addElement('hidden', 'id', $url_data['id']);
    // If we're still with localhost (should only happen at the very beginning)
    // offer the current URL by default. Once this has been saved, no more
    // magic will happen, ever.
    if ($url_data['id'] === 1 && $url_data['url'] === 'http://localhost/') {
        $https = api_is_https() ? 'https://' : 'http://';
        $url_data['url'] = $https.$_SERVER['HTTP_HOST'].'/';
    }
    $form->setDefaults($url_data);
    $submit_name = get_lang('Add URL');
}

$form->addButtonCreate($submit_name);

//the first url with id = 1 will be always active
if (isset($_GET['url_id']) && 1 != $_GET['url_id']) {
    $form->addElement('checkbox', 'active', null, get_lang('active'));
}

if ($form->validate()) {
    $check = Security::check_token('post');
    if ($check) {
        $url_array = $form->getSubmitValues();
        $url = Security::remove_XSS($url_array['url']);
        $description = Security::remove_XSS($url_array['description']);
        $active = isset($url_array['active']) ? (int) $url_array['active'] : 0;
        $url_id = isset($url_array['id']) ? (int) $url_array['id'] : 0;
        $url_to_go = 'access_urls.php';
        if (!empty($url_id)) {
            //we can't change the status of the url with id=1
            if (1 == $url_id) {
                $active = 1;
            }
            // Checking url
            if ('/' == substr($url, strlen($url) - 1, strlen($url))) {
                UrlManager::update($url_id, $url, $description, $active);
            } else {
                UrlManager::update($url_id, $url.'/', $description, $active);
            }
            $url_to_go = 'access_urls.php';
            $message = get_lang('The URL has been edited');
        } else {
            $num = UrlManager::url_exist($url);
            $url_to_go = 'access_url_edit.php';
            $message = get_lang('This URL already exists, please select another URL');
            if (0 === $num) {
                // checking url
                if ('/' == substr($url, strlen($url) - 1, strlen($url))) {
                    $accessUrl = UrlManager::add($url, $description, $active);
                } else {
                    //create
                    $accessUrl = UrlManager::add($url.'/', $description, $active);
                }
                if (null !== $accessUrl) {
                    $message = get_lang('The URL has been added');
                    $url_to_go = 'access_urls.php';
                }
            }
        }

        Security::clear_token();
        $tok = Security::get_token();
        Display::addFlash(Display::return_message($message));
        header('Location: '.$url_to_go.'?sec_token='.$tok);
        exit;
    }
} else {
    if (isset($_POST['submit'])) {
        Security::clear_token();
    }
    $token = Security::get_token();
    $form->addElement('hidden', 'sec_token');
    $form->setConstants(['sec_token' => $token]);
}

$tool_name = get_lang('Add URL');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'access_urls.php', 'name' => get_lang('Multiple access URL / Branding')];

Display::display_header($tool_name);
$form->display();
Display::display_footer();
