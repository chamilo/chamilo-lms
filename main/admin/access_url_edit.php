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

// Create the form
$form = new FormValidator('add_url');

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
            if (substr($url, strlen($url) - 1, strlen($url)) == '/') {
                UrlManager::update($url_id, $url, $description, $active);
            } else {
                UrlManager::update($url_id, $url.'/', $description, $active);
            }
            // URL Images
            $url_images_dir = api_get_path(SYS_PATH).'custompages/url-images/';
            $image_fields = ['url_image_1', 'url_image_2', 'url_image_3'];
            foreach ($image_fields as $image_field) {
                if ($_FILES[$image_field]['error'] == 0) {
                    // Hardcoded: only PNG files allowed
                    $fileFields = explode('.', $_FILES[$image_field]['name']);
                    if (end($fileFields) === 'png') {
                        if (file_exists($url_images_dir.$url_id.'_'.$image_field.'.png')) {
                            // if the file exists, we have to remove it before move_uploaded_file
                            unlink($url_images_dir.$url_id.'_'.$image_field.'.png');
                        }
                        move_uploaded_file(
                            $_FILES[$image_field]['tmp_name'],
                            $url_images_dir.$url_id.'_'.$image_field.'.png'
                        );
                    }
                }
            }
            $url_to_go = 'access_urls.php';
            $message = get_lang('URLEdited');
        } else {
            $num = UrlManager::url_exist($url);
            if ($num == 0) {
                // checking url
                if (substr($url, strlen($url) - 1, strlen($url)) == '/') {
                    UrlManager::add($url, $description, $active);
                } else {
                    //create
                    UrlManager::add($url.'/', $description, $active);
                }
                $message = get_lang('URLAdded');
                $url_to_go = 'access_urls.php';
            } else {
                $url_to_go = 'access_url_edit.php';
                $message = get_lang('URLAlreadyAdded');
            }
            // URL Images
            $url .= (substr($url, strlen($url) - 1, strlen($url)) == '/') ? '' : '/';
            $url_id = UrlManager::get_url_id($url);
            $url_images_dir = api_get_path(SYS_PATH).'custompages/url-images/';
            $image_fields = ["url_image_1", "url_image_2", "url_image_3"];
            foreach ($image_fields as $image_field) {
                if ($_FILES[$image_field]['error'] == 0) {
                    // Hardcoded: only PNG files allowed
                    $fileFields = explode('.', $_FILES[$image_field]['name']);
                    if (end($fileFields) == 'png') {
                        move_uploaded_file(
                            $_FILES[$image_field]['tmp_name'],
                            $url_images_dir.$url_id.'_'.$image_field.'.png'
                        );
                    }
                }
            }
        }
        Security::clear_token();
        $tok = Security::get_token();
        Display::addFlash(Display::return_message($message));
        header('Location: '.$url_to_go.'?sec_token='.$tok);
        exit();
    }
} else {
    if (isset($_POST['submit'])) {
        Security::clear_token();
    }
    $token = Security::get_token();
    $form->addElement('hidden', 'sec_token');
    $form->setConstants(['sec_token' => $token]);
}

$form->addElement('text', 'url', 'URL');
$form->addRule('url', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('url', '', 'maxlength', 254);
$form->addElement('textarea', 'description', get_lang('Description'));

//the first url with id = 1 will be always active
if (isset($_GET['url_id']) && $_GET['url_id'] != 1) {
    $form->addElement('checkbox', 'active', null, get_lang('Active'));
}

$defaults['url'] = 'http://';
$form->setDefaults($defaults);

$submit_name = get_lang('AddUrl');
if (isset($_GET['url_id'])) {
    $url_id = (int) $_GET['url_id'];
    $num_url_id = UrlManager::url_id_exist($url_id);
    if ($num_url_id != 1) {
        header('Location: access_urls.php');
        exit();
    }
    $url_data = UrlManager::get_url_data_from_id($url_id);
    $form->addElement('hidden', 'id', $url_data['id']);
    $form->setDefaults($url_data);
    $submit_name = get_lang('AddUrl');
}

if (!api_is_multiple_url_enabled()) {
    header('Location: index.php');
    exit;
}

$tool_name = get_lang('AddUrl');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'access_urls.php', 'name' => get_lang('MultipleAccessURLs')];

Display::display_header($tool_name);

// URL Images
$form->addElement('file', 'url_image_1', 'URL Image 1 (PNG)');
$form->addElement('file', 'url_image_2', 'URL Image 2 (PNG)');
$form->addElement('file', 'url_image_3', 'URL Image 3 (PNG)');

// Submit button
$form->addButtonCreate($submit_name);
$form->display();

Display::display_footer();
