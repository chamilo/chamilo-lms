<?php
/* For licensing terms, see /license.txt */

/**
 * Management of legal conditions.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

if ('true' !== api_get_setting('allow_terms_conditions')) {
    api_not_allowed(true);
}

// Create the form
$form = new FormValidator('addlegal');

$defaults = [];
$termPreview = [
    'type' => 0,
    'content' => '',
    'changes' => '',
];

$extraField = new ExtraField('terms_and_condition');

$types = LegalManager::getTreatmentTypeList();

foreach ($types as $variable => $name) {
    $label = 'PersonalData'.ucfirst($name).'Title';
    $params = [
        'variable' => $variable,
        'display_text' => $label,
        'value_type' => ExtraField::FIELD_TYPE_TEXTAREA,
        'default_value' => '',
        'visible' => true,
        'changeable' => true,
        'filter' => true,
        'visible_to_self' => true,
        'visible_to_others' => true,
    ];
    $extraField->save($params);
}

if ($form->validate()) {
    $check = Security::check_token('post');
    if ($check) {
        $values = $form->getSubmitValues();
        $lang = $values['language'];
        // language id
        $lang = api_get_language_id($lang);
        $type = 0;
        if (isset($values['type'])) {
            $type = $values['type'];
        }
        $content = '';
        if (isset($values['content'])) {
            $content = $values['content'];
        }
        $changes = '';
        if (isset($values['changes'])) {
            $changes = $values['changes'];
        }

        $submit = $values['send'];

        $default['content'] = $content;
        if (isset($values['language'])) {
            if ('back' == $submit) {
                header('Location: legal_add.php');
                exit;
            } elseif ('save' === $submit) {
                $id = LegalManager::add($lang, $content, $type, $changes, $values);
                if (!empty($id)) {
                    Display::addFlash(Display::return_message(get_lang('Term and condition saved'), 'success'));
                } else {
                    Display::addFlash(Display::return_message(get_lang('Term and condition not saved'), 'warning'));
                }
                Security::clear_token();
                $tok = Security::get_token();
                header('Location: legal_list.php?sec_token='.$tok);
                exit();
            } elseif ('preview' === $submit) {
                $defaults['type'] = $type;
                $defaults['content'] = $content;
                $defaults['changes'] = $changes;
                $termPreview = $defaults;
                $termPreview['type'] = (int) $_POST['type'];
            } else {
                $myLang = $_POST['language'];
                if (isset($_POST['language'])) {
                    $allLangs = api_get_languages();
                    if (in_array($myLang, array_keys($allLangs))) {
                        $language = api_get_language_id($myLang);
                        $termPreview = LegalManager::get_last_condition($language);
                        $defaults = $termPreview;
                        if (!$termPreview) {
                            // there are not terms and conditions
                            $termPreview['type'] = -1;
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
$form->addElement('header', get_lang('Display a Terms DisplayTermsConditions Conditions statement on the registration page, require visitor to accept the TDisplayTermsConditionsC to register.'));
$jqueryReady = '';

if (isset($_POST['language'])) {
    $form->addElement('static', Security::remove_XSS($_POST['language']));
    $form->addElement('hidden', 'language', Security::remove_XSS($_POST['language']));
    $form->addHtmlEditor(
        'content',
        get_lang('Content'),
        true,
        false,
        ['ToolbarSet' => 'terms_and_conditions', 'Width' => '100%', 'Height' => '250']
    );

    $form->addElement('radio', 'type', '', get_lang('HTML'), '0');
    $form->addElement('radio', 'type', '', get_lang('Page Link'), '1');

    $preview = LegalManager::show_last_condition($termPreview);

    if (-1 != $termPreview['type']) {
        $preview = LegalManager::replaceTags($preview);
        $form->addElement('label', get_lang('Preview'), $preview);
    }

    $termId = isset($termPreview['id']) ? $termPreview['id'] : 0;
    $returnParams = $extraField->addElements(
        $form,
        $termId,
        [],
        false,
        false,
        [],
        [],
        [],
        false,
        true,
        [],
        [],
        false,
        [],
        [],
        false,
        true
    );

    $jqueryReady = $returnParams['jquery_ready_content'];

    $form->addElement('textarea', 'changes', get_lang('Explain changes'), ['width' => '20']);

    // Submit & preview button
    $buttons = '<div class="row" align="center">
                <div class="formw">
                <button type="submit" class="btn btn--plain back" 	 name="send" value="back">'.get_lang('Back').'</button>
                <button type="submit" class="btn btn--plain search" name="send" value="preview">'.get_lang('Preview').'</button>
                <button type="submit" class="btn btn--primary save" 	 name="send" value="save">'.get_lang('Save').'</button>
                </div>
            </div>';
    $form->addElement('html', $buttons);
} else {
    $form->addSelectLanguage('language', get_lang('Language'), null, []);
    $form->addButtonSearch(get_lang('Load'), 'send');
}

$toolName = get_lang('Add terms and conditions');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

// the $jquery_ready_content variable collects all functions that will be load in the $(document).ready javascript function
$htmlHeadXtra[] = '<script>
$(function () {
    '.$jqueryReady.'
});
</script>';

Display::display_header($toolName);

echo '<script>
function sendlang() {
	document.addlegal.sec_token.value=\''.$token.'\';
	document.addlegal.submit();
}
</script>';

// action menu
echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/legal_list.php">'.
    Display::return_icon('search.gif', get_lang('Edit terms and conditions'), '').
    get_lang('All versions').'</a>';
echo '</div>';

$form->setDefaults($defaults);
$form->display();
Display::display_footer();
