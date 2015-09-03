<?php
/* For licensing terms, see /license.txt */
//Initialization
require_once('language.php');
require_once('../inc/global.inc.php');
require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';

//Removes some unwanted elementend of the form object
$content['form']->setLayout(FormValidator::LAYOUT_INLINE);
$content['form']->setAttribute('class', 'registration-form');
$content['form']->removeElement('lastname', false);
$content['form']->removeElement('firstname', false);
$content['form']->removeElement('email', false);
$content['form']->removeElement('extra_date_of_birth', false);
$content['form']->removeElement('pass1', false);
$content['form']->removeElement('pass2', false);
$content['form']->removeElement('extra_mail_notify_invitation');
$content['form']->removeElement('extra_mail_notify_message');
$content['form']->removeElement('extra_mail_notify_group_message');
$content['form']->removeElement('official_code');
$content['form']->removeElement('phone');
$content['form']->removeElement('language');
$content['form']->removeElement('submit');

if (isset($content['form']->_elementIndex['status'])) {
    $content['form']->removeElement('status');
    $content['form']->removeElement('status');
}

$content['form']->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
$content['form']->addElement('text', 'lastname', get_lang('LastName'), array('size' => 40));
$content['form']->addElement('text', 'email', get_lang('EmailAddress'), array('size' => 40));
$content['form']->addElement('DatePicker', 'extra_date_of_birth', 'Fecha de nacimiento');
$content['form']->addElement('password', 'pass1', get_lang('Pass'), array('id' => 'pass1', 'size' => 20, 'autocomplete' => 'off'));
$content['form']->addElement('password', 'pass2', get_lang('Confirmation'), array('id' => 'pass2', 'size' => 20, 'autocomplete' => 'off'));
$content['form']->addButton('submit', get_lang('Regístrate'), null, 'press');

$renderer = & $content['form']->defaultRenderer();

$elementTemplate = <<<HTML
    <div class="col-md-6">
        <div class="form-group">
            <label><!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}</label>
            {element}
            <!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->
        </div>
    </div>
HTML;

$dateElementTemplate = <<<HTML
    <div class="col-md-6">
        <div class="form-group">
            <label><!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}</label>
            <div class="input-group">
                <span class="input-group-addon" id="date-trigger">
                    {element}
                </span>
                <input class="form-control" type="text" readonly id="extra_date_of_birth_alt">
            </div>
            <!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->
            <script>
                $(function () {
                    $('#date-trigger img').on('click', function () {
                        $( "#extra_date_of_birth" ).datepicker( "widget" ).css('z-index', 1051);
                    });
                });
            </script>
        </div>
    </div>
</div>
HTML;

$buttonTemplate = <<<HTML
<div class="row">
    <div class="col-md-12">
        {element}
        <div class="terms">
            Al crear una cuenta, aceptas las Condiciones del servicio y la Política de privacidad de Tademi
        </div>
        <hr class="separator">
    </div>
</div>
HTML;

$leftElement = <<<EOT
<div class="row">
    $elementTemplate
EOT;

$rightElement = <<<EOT
    $elementTemplate
</div>
EOT;

$renderer->setElementTemplate($leftElement, 'firstname');
$renderer->setElementTemplate($leftElement, 'email');
$renderer->setElementTemplate($leftElement, 'pass1');
$renderer->setElementTemplate($rightElement, 'lastname');
$renderer->setElementTemplate($dateElementTemplate, 'extra_date_of_birth');
$renderer->setElementTemplate($rightElement, 'pass2');
$renderer->setElementTemplate($buttonTemplate, 'submit');

//View
if (!isset($_GET['hide_headers']) || $_GET['hide_headers'] != 1) {
    Display::display_header('aaa');
}

echo $content['form']->returnForm();

if (!isset($_GET['hide_headers']) || $_GET['hide_headers'] != 1) {
    Display::display_footer();
}
