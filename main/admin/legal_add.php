<?php
/* For licensing terms, see /license.txt */

/**
 * Management of legal conditions
 * @package chamilo.admin
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

if (api_get_setting('allow_terms_conditions') !== 'true') {
    api_not_allowed(true);
}

// Create the form
$form = new FormValidator('addlegal');

$defaults = array();
$term_preview = array(
    'type' => 0,
    'content' => '',
    'changes' => ''
);
if ($form->validate()) {
    $check = Security::check_token('post');
    if ($check) {
        $values = $form->getSubmitValues();
        $lang = $values['language'];
        //language id
        $lang = api_get_language_id($lang);

        if (isset($values['type'])) {
            $type = $values['type'];
        } else {
            $type = 0;
        }
        if (isset($values['content'])) {
            $content = $values['content'];
        } else {
            $content = '';
        }
        if (isset($values['changes'])) {
            $changes = $values['changes'];
        } else {
            $changes = '';
        }
        $submit = $values['send'];

        $default['content'] = $content;
        if (isset($values['language'])) {
            if ($submit == 'back') {
                header('Location: legal_add.php');
                exit;
            } elseif ($submit == 'save') {
                $insert_result = LegalManager::add($lang, $content, $type, $changes);
                if ($insert_result) {
                    $message = get_lang('TermAndConditionSaved');
                } else {
                    $message = get_lang('TermAndConditionNotSaved');
                }
                Security::clear_token();
                $tok = Security::get_token();
                Display::addFlash(Display::return_message($message));
                header('Location: legal_list.php?sec_token='.$tok);
                exit();
            } elseif ($submit == 'preview') {
                $defaults['type'] = $type;
                $defaults['content'] = $content;
                $defaults['changes'] = $changes;
                $term_preview = $defaults;
                $term_preview['type'] = intval($_POST['type']);
            } else {
                $my_lang = $_POST['language'];
                if (isset($_POST['language'])) {
                    $all_langs = api_get_languages();
                    if (in_array($my_lang, $all_langs['folder'])) {
                        $language = api_get_language_id($my_lang);
                        $term_preview = LegalManager::get_last_condition($language);
                        $defaults = $term_preview;
                        if (!$term_preview) {
                            // there are not terms and conditions
                            $term_preview['type'] = -1;
                            $defaults['type'] = 0;
                        }
                    }
                }
            }
        }
    }
}

$form->setDefaults($defaults);

if (isset($_POST['send'])) {
    Security::clear_token();
}
$token = Security::get_token();

$form->addElement('hidden', 'sec_token');
$defaults['sec_token'] = $token;
$form->addElement('header', get_lang('DisplayTermsConditions'));

if (isset($_POST['language'])) {

	$form->addElement('static', Security::remove_XSS($_POST['language']));
	$form->addElement('hidden', 'language', Security::remove_XSS($_POST['language']));
    $form->addHtmlEditor(
        'content',
        get_lang('Content'),
        true,
        false,
        array('ToolbarSet' => 'terms_and_conditions', 'Width' => '100%', 'Height' => '250')
    );

    $form->addElement('radio', 'type', '', get_lang('HTMLText'), '0');
    $form->addElement('radio', 'type', '', get_lang('PageLink'), '1');
    $form->addElement('textarea', 'changes', get_lang('ExplainChanges'), array('width' => '20'));

    $preview = LegalManager::show_last_condition($term_preview);

    if ($term_preview['type'] != -1) {
        $preview = LegalManager::replaceTags($preview);
        $form->addElement('label', get_lang('Preview'), $preview);
    }

    // Submit & preview button
    $buttons = '<div class="row" align="center">
                <div class="formw">
                <button type="submit" class="btn btn-default back" 	 name="send" value="back">'.get_lang('Back').'</button>
                <button type="submit" class="btn btn-default search" name="send" value="preview">'.get_lang('Preview').'</button>
                <button type="submit" class="btn btn-primary save" 	 name="send" value="save">'.get_lang('Save').'</button>
                </div>
            </div>';
    $form->addElement('html', $buttons);

} else {
    $form->addSelectLanguage('language', get_lang('Language'), null, array());
	$form->addButtonSearch(get_lang('Load'), 'send');

}

$tool_name = get_lang('AddTermsAndConditions');
$interbreadcrumb[] = array("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
Display :: display_header($tool_name);

echo '<script>
function sendlang(){
	document.addlegal.sec_token.value=\''.$token.'\';
	document.addlegal.submit();
}
</script>';

// action menu
echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/legal_list.php">'.
    Display::return_icon('search.gif', get_lang('EditTermsAndConditions'), '').get_lang('AllVersions').'</a>';
echo '</div>';

$form->setDefaults($defaults);
$form->display();
Display :: display_footer();
