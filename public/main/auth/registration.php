<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\Entity\PageCategory;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Chamilo\CoreBundle\Helpers\ContainerHelper;
use ChamiloSession as Session;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

$kernel = null;

require_once __DIR__.'/../inc/global.inc.php';

/**
 * This script displays a form for registering new users.
 */

// Quick hack to adapt the registration form result to the selected registration language.
if (!empty($_POST['language'])) {
    $_GET['language'] = $_POST['language'];
}

$hideHeaders = isset($_GET['hide_headers']);

/**
 * Helper: normalize a settings value into a list of strings.
 * Supports CSV storage, JSON arrays, and Sylius settings payloads (options/fields).
 */
$normalizeSettingList = static function ($raw): array {
    if (null === $raw || false === $raw) {
        return [];
    }

    // Some settings can return the string "false"
    if (is_string($raw)) {
        $rawTrim = trim($raw);
        if ($rawTrim === '' || strtolower($rawTrim) === 'false') {
            return [];
        }

        // JSON payload support
        $firstChar = $rawTrim[0] ?? '';
        if ($firstChar === '[' || $firstChar === '{') {
            $decoded = json_decode($rawTrim, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            }
        }
    }

    // Sylius-like payloads: ['options' => [...]] or ['fields' => [...]]
    if (is_array($raw)) {
        if (isset($raw['options']) && is_array($raw['options'])) {
            $raw = $raw['options'];
        } elseif (isset($raw['fields']) && is_array($raw['fields'])) {
            $raw = $raw['fields'];
        }
    }

    // Plain array
    if (is_array($raw)) {
        $list = [];
        foreach ($raw as $v) {
            if (is_string($v)) {
                $v = trim($v);
                if ($v !== '') {
                    $list[] = $v;
                }
            }
        }
        return array_values(array_unique($list));
    }

    // CSV / newline-separated string
    if (is_string($raw)) {
        $raw = str_replace(["\r\n", "\n", "\r", ";"], [",", ",", ",", ","], $raw);
        $parts = array_map('trim', explode(',', $raw));
        $parts = array_values(array_filter($parts, static function ($v) {
            return is_string($v) && $v !== '' && strtolower($v) !== 'false';
        }));
        return array_values(array_unique($parts));
    }

    return [];
};

/**
 * Helper: mark a QuickForm element as required so templates can render the "*" marker.
 */
$markRequired = static function (FormValidator $form, string $name): void {
    if (!method_exists($form, 'getElement') || !method_exists($form, 'setRequired')) {
        return;
    }

    try {
        $element = $form->getElement($name);
        if ($element) {
            $form->setRequired($element);
        }
    } catch (\Throwable $e) {
        // Do nothing: element might not exist in some contexts.
    }
};

/**
 * Registration settings (backward-compatible).
 * Note: We ALWAYS show the role selector (UI requirement),
 * but we still enforce platform rules (security) by disabling teacher role
 * when the setting is disabled, and forcing STUDENT on submit.
 */
$allowTeacherRegistrationRaw = api_get_setting('registration.allow_registration_as_teacher');
if (null === $allowTeacherRegistrationRaw || '' === (string) $allowTeacherRegistrationRaw) {
    $allowTeacherRegistrationRaw = api_get_setting('allow_registration_as_teacher');
}
$allowTeacherRegistration = \in_array((string) $allowTeacherRegistrationRaw, ['true', '1', 'yes'], true);

// Expose to JS (for UI disabling).
$htmlHeadXtra[] = '<script>window.CHAMILO_ALLOW_TEACHER_REGISTRATION = '.($allowTeacherRegistration ? 'true' : 'false').';</script>';

$allowedFields = [
    'official_code',
    'phone',
    'status',
    'language',
    'extra_fields',
    'address',
];

$allowedFieldsConfiguration = api_get_setting('registration.allow_fields_inscription', true);
if ('false' !== $allowedFieldsConfiguration) {
    $fieldsFromConfig = $allowedFieldsConfiguration['fields'] ?? [];
    if (!is_array($fieldsFromConfig)) {
        $fieldsFromConfig = $normalizeSettingList($fieldsFromConfig);
    }

    $allowedFields = $fieldsFromConfig;

    $extraFromConfig = $allowedFieldsConfiguration['extra_fields'] ?? [];
    if (!is_array($extraFromConfig)) {
        $extraFromConfig = $normalizeSettingList($extraFromConfig);
    }
    $allowedFields['extra_fields'] = $extraFromConfig;
}

// UI requirement: always show role selector even if config removes it.
if (!in_array('status', $allowedFields, true)) {
    $allowedFields[] = 'status';
}

$requiredProfileFieldsRaw = api_get_setting('registration.required_profile_fields', true);
$requiredProfileFieldsTokens = $normalizeSettingList($requiredProfileFieldsRaw);

$fieldKeyMap = [
    // Settings schema values
    'officialcode' => 'official_code',
    'email' => 'email',
    'language' => 'language',
    'phone' => 'phone',

    // Also accept already-normalized keys (defensive)
    'official_code' => 'official_code',
];

$requiredProfileFields = [];
foreach ($requiredProfileFieldsTokens as $token) {
    $t = strtolower(trim((string) $token));
    if ($t === '') {
        continue;
    }
    if (isset($fieldKeyMap[$t])) {
        $requiredProfileFields[$fieldKeyMap[$t]] = true;
    }
}

$hasRequiredProfileConfig = !empty($requiredProfileFields);

// Required flags derived from required_profile_fields (authoritative if configured)
$isEmailRequiredFromRequiredProfile = $hasRequiredProfileConfig && isset($requiredProfileFields['email']);
$isLanguageRequiredFromRequiredProfile = $hasRequiredProfileConfig && isset($requiredProfileFields['language']);
$isPhoneRequiredFromRequiredProfile = $hasRequiredProfileConfig && isset($requiredProfileFields['phone']);
$isOfficialCodeRequiredFromRequiredProfile = $hasRequiredProfileConfig && isset($requiredProfileFields['official_code']);

// Force mandatory fields to be visible in the registration form
$forcedVisibleFields = [];
if ($isLanguageRequiredFromRequiredProfile) {
    $forcedVisibleFields[] = 'language';
}
if ($isPhoneRequiredFromRequiredProfile) {
    $forcedVisibleFields[] = 'phone';
}
if ($isOfficialCodeRequiredFromRequiredProfile) {
    $forcedVisibleFields[] = 'official_code';
}

foreach ($forcedVisibleFields as $f) {
    if (!in_array($f, $allowedFields, true)) {
        $allowedFields[] = $f;
    }
}

$pluginTccDirectoryPath = api_get_path(SYS_PLUGIN_PATH) . 'logintcc';
$isTccEnabled = (is_dir($pluginTccDirectoryPath) && Container::getPluginHelper()->isPluginEnabled('logintcc'));
$webserviceUrl = '';
$hash = '';

if ($isTccEnabled) {
    $webserviceUrl = (string) api_get_plugin_setting('logintcc', 'webservice_url');
    $hash = (string) api_get_plugin_setting('logintcc', 'hash');

    // TCC plugin
    $tccWebserviceUrlJs = json_encode(rtrim($webserviceUrl, '/'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
    $tccHashJs          = json_encode($hash, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
    $unknownUserMsgJs   = json_encode((string) get_lang('Unknown user'), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);

    $htmlHeadXtra[] = <<<EOD
<script>
$(document).ready(function() {
    const tccWebserviceUrl = {$tccWebserviceUrlJs};
    const tccHash = {$tccHashJs};
    const unknownUserMsg = {$unknownUserMsgJs};

    $("#search_user").click(function() {
        var data = new Object();
        data.Mail = $("input[name='email']").val();
        data.HashKey = tccHash;

        $.ajax({
            url: tccWebserviceUrl + "/IsExistEmail",
            data: JSON.stringify(data),
            dataType: "json",
            type: "POST",
            contentType: "application/json; charset=utf-8",
            success: function (data, status) {
                if (data && data.d && data.d.Exist) {
                    var monU = data.d.User;

                    $("input[name='extra_tcc_user_id']").val(monU.UserID);
                    $("input[name='extra_tcc_hash_key']").val(monU.HashKey);

                    var \$radios = $("input:radio[name='extra_terms_genre[extra_terms_genre]']");
                    if (monU.Genre == "Masculin") {
                        \$radios.filter("[value=homme]").prop("checked", true);
                    } else {
                        \$radios.filter("[value=femme]").prop("checked", true);
                    }

                    $("input[name='lastname']").val(monU.Nom);
                    $("input[name='firstname']").val(monU.Prenom);

                    var date = monU.DateNaissance; // 30/06/1986
                    if (date != "") {
                        var parts = date.split("/");
                        $("#extra_terms_datedenaissance").datepicker("setDate", new Date(parts[2], parts[1], parts[0]));
                    }

                    if (monU.Langue == "fr-FR") {
                        $("#language").selectpicker("val", "french");
                        $("#language").selectpicker("render");
                    }

                    if (monU.Langue == "de-DE") {
                        $("#language").selectpicker("val", "german");
                        $("#language").selectpicker("render");
                    }

                    $("input[name='extra_terms_nationalite']").val(monU.Nationalite);
                    $("input[name='extra_terms_paysresidence']").val(monU.PaysResidence);
                    $("input[name='extra_terms_adresse']").val(monU.Adresse);
                    $("input[name='extra_terms_codepostal']").val(monU.CP);
                    $("input[name='extra_terms_ville']").val(monU.Ville);
                } else {
                    alert(unknownUserMsg);
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                alert(textStatus);
            }
        });

        return false;
    });
});
</script>
EOD;
}

$extraFieldsLoaded = false;
$htmlHeadXtra[] = api_get_password_checker_js('#username', '#pass1');

// Avoid JS syntax errors with translations (apostrophes, newlines, etc.).
$registeringTextJs = json_encode((string) get_lang('Registering'), JSON_UNESCAPED_UNICODE);

$htmlHeadXtra[] = <<<EOD
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[name="registration"]');
    if (!form) return;

    const registeringText = {$registeringTextJs};

    form.addEventListener('submit', function() {
        const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitButtons.forEach(btn => {
            btn.disabled = true;
            btn.classList.add('disabled');
            if (btn.tagName === 'BUTTON') {
                btn.innerText = registeringText;
            } else {
                btn.value = registeringText;
            }
        });
    });
});
</script>
EOD;

/**
 * Tailwind-only UI polish for legacy QuickForm output (non-invasive).
 * This keeps existing functionality and only enhances classes at runtime.
 */
$htmlHeadXtra[] = <<<EOD
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form[name="registration"]');
  if (!form) return;

  // Form container
  form.classList.add('max-w-4xl','mx-auto','bg-white','rounded-2xl','border','border-gray-25','shadow-sm','p-6','md:p-8');

  // Inputs / selects / textareas
  const controls = form.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], input[type="tel"], select, textarea');
  controls.forEach(el => {
    // Skip hidden fields
    if (el.type === 'hidden') return;

    el.classList.add(
      'w-full','rounded-xl','border','border-gray-25','bg-white',
      'px-4','py-3','text-gray-90',
      'focus:outline-none','focus:ring-2','focus:ring-gray-60','focus:border-gray-60'
    );
  });

  // Labels
  form.querySelectorAll('label').forEach(l => {
    l.classList.add('text-sm','font-medium','text-gray-90');
  });

  // Required markers often appear as <span class="form_required">*</span>
  form.querySelectorAll('.form_required').forEach(s => {
    s.classList.add('text-red-60','font-semibold');
  });

  // Error messages
  form.querySelectorAll('.form_error, .error').forEach(err => {
    err.classList.add('text-red-60','text-sm','mt-1');
  });

  // Improve spacing between rows (best-effort)
  form.querySelectorAll('tr').forEach(tr => tr.classList.add('align-top'));
});
</script>
EOD;

// User is not allowed if Terms and Conditions are disabled and registration is disabled too.
$isCreatingIntroPage = isset($_GET['create_intro_page']);
$isPlatformAdmin = api_is_platform_admin();

$isNotAllowedHere = (
    'false' === api_get_setting('allow_terms_conditions') &&
    'false' === api_get_setting('allow_registration')
);

if ($isNotAllowedHere && !($isCreatingIntroPage && $isPlatformAdmin)) {
    api_not_allowed(
        true,
        get_lang('Sorry, you are trying to access the registration page for this portal, but registration is currently disabled. Please contact the administrator (see contact information in the footer). If you already have an account on this site.')
    );
}

$settingConditions = api_get_setting('profile.show_conditions_to_user', true);
$extraConditions = 'false' !== $settingConditions ? $settingConditions : [];
if ($extraConditions && isset($extraConditions['conditions'])) {
    // Create user extra fields for the conditions.
    $userExtraField = new ExtraField('user');

    // Use the resolved array to avoid relying on $settingConditions type.
    $extraConditions = $extraConditions['conditions'];

    foreach ($extraConditions as $condition) {
        $exists = $userExtraField->get_handler_field_info_by_field_variable($condition['variable']);
        if (false == $exists) {
            $params = [
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
                'variable' => $condition['variable'],
                'display_text' => $condition['display_text'],
                'default_value' => '',
                'visible_to_self' => 0,
                'visible_to_others' => 0,
                'changeable' => 0,
                'filter' => 0,
            ];
            $userExtraField->save($params);
        }
    }
}

$form = new FormValidator('registration');
$userAlreadyRegisteredShowTerms = false;
$termRegistered = Session::read('term_and_condition');
if ('true' === api_get_setting('allow_terms_conditions')) {
    $userAlreadyRegisteredShowTerms = isset($termRegistered['user_id']);
    // Ofaj change
    if (true === api_is_anonymous() && 'course' === api_get_setting('workflows.load_term_conditions_section')) {
        $userAlreadyRegisteredShowTerms = false;
    }
}

$sessionPremiumChecker = Session::read('SessionIsPremium');
$sessionId = Session::read('sessionId');

// Direct Link Session Subscription feature #12220
$sessionRedirect = isset($_REQUEST['s']) && !empty($_REQUEST['s']) ? $_REQUEST['s'] : null;
$onlyOneCourseSessionRedirect = isset($_REQUEST['cr']) && !empty($_REQUEST['cr']) ? $_REQUEST['cr'] : null;

if ('true' === api_get_setting('session.allow_redirect_to_session_after_inscription_about')) {
    if (!empty($sessionRedirect)) {
        Session::write('session_redirect', $sessionRedirect);
        Session::write('only_one_course_session_redirect', $onlyOneCourseSessionRedirect);
    }
}

/**
 * Build the redirect URL for a "direct registration" link.
 * - Default: course home.
 * - If an exercise ID is provided: redirect to the exercise tool inside the course.
 */
$buildDirectLinkRedirectUrl = static function (int $courseId, int $exerciseId = 0): string {
    // Course home
    $courseHomeUrl = api_get_path(WEB_PATH) . 'course/' . $courseId . '/home?sid=0';

    if ($exerciseId <= 0) {
        return $courseHomeUrl;
    }

    $courseInfo = api_get_course_info_by_id($courseId);
    $courseCode = $courseInfo['code'] ?? $courseInfo['course_code'] ?? $courseInfo['directory'] ?? '';

    if (empty($courseCode)) {
        return $courseHomeUrl;
    }

    // Go to the exercise entrypoint
    $query = http_build_query([
        'cid' => $courseId,
        'sid' => 0,
        'gid' => 0,
        'exerciseId' => $exerciseId,
    ]);

    return api_get_path(WEB_CODE_PATH) . 'exercise/overview.php?' . $query;
};

$courseIdRedirect = isset($_REQUEST['c']) && !empty($_REQUEST['c']) ? (int) $_REQUEST['c'] : null;
$exercise_redirect = isset($_REQUEST['e']) && !empty($_REQUEST['e']) ? (int) $_REQUEST['e'] : 0;

if (!empty($courseIdRedirect)) {
    $courseInfo = api_get_course_info_by_id($courseIdRedirect);
    $visibility = (int) ($courseInfo['visibility'] ?? -1);

    $isOpenCourse = in_array(
        $visibility,
        [COURSE_VISIBILITY_OPEN_PLATFORM, COURSE_VISIBILITY_OPEN_WORLD],
        true
    );

    if (!api_is_anonymous()) {
        if ($isOpenCourse) {
            if ($exercise_redirect > 0) {
                CourseManager::autoSubscribeToCourse($courseIdRedirect);
            }

            header('Location: ' . $buildDirectLinkRedirectUrl($courseIdRedirect, $exercise_redirect));
            exit;
        }

        header('Location: ' . api_get_path(WEB_PATH) . 'course/' . $courseIdRedirect . '/about');
        exit;
    }
    Session::write('course_redirect', $courseIdRedirect);
    Session::write('exercise_redirect', $exercise_redirect);
}

// allow_registration can be 'true', 'false', 'approval' or 'confirmation'. Only 'false' hides the form.
if (false === $userAlreadyRegisteredShowTerms && 'false' !== api_get_setting('allow_registration')) {
    /**
     * ROLE SELECTOR (Learner / Teacher)
     * UI: always shown (forced in allowed fields).
     * Security: teacher option disabled if platform forbids it, and backend forces STUDENT on submit.
     */
    if (in_array('status', $allowedFields, true)) {
        $iconSize = defined('ICON_SIZE_MEDIUM') ? ICON_SIZE_MEDIUM : 28;

        $renderIcon = static function (ObjectIcon|string $icon) use ($iconSize): string {
            $iconName = $icon instanceof ObjectIcon ? $icon->value : $icon;

            if (method_exists(Display::class, 'getMdiIcon')) {
                return Display::getMdiIcon($iconName, 'text-gray-70', null, $iconSize);
            }

            return '';
        };

        $studentIcon = $renderIcon(ObjectIcon::USER);
        $teacherIcon = $renderIcon(ObjectIcon::TEACHER);

        $title = get_lang('What do you want to do?');
        $notAvailable = get_lang('Not available');

        $studentValue = (int) STUDENT;
        $teacherValue = (int) COURSEMANAGER;

        $teacherValueJs = json_encode((string) $teacherValue, JSON_UNESCAPED_UNICODE);

        // Title (with required asterisk) above cards.
        $form->addHtml('
            <div class="mb-3">
                <div class="text-lg font-semibold text-gray-90">'.$title.' <span class="text-red-60">*</span></div>
            </div>
        ');

        // Render our own cards (stable grid), and keep QuickForm radios hidden for validation/submission.
        $teacherHint = $allowTeacherRegistration ? '' : $notAvailable;
        $teacherDisabledAttr = $allowTeacherRegistration ? '' : ' disabled aria-disabled="true"';

        $form->addHtml('
            <div id="role-cards" class="role-cards-grid mb-6">
                <button
                    type="button"
                    class="role-card js-role-card"
                    data-role-value="'.$studentValue.'"
                    aria-pressed="false"
                >
                    <span class="role-card__icon">'.$studentIcon.'</span>
                    <span class="role-card__text">
                        <span class="role-card__title">'.get_lang('Follow courses').'</span>
                    </span>
                </button>

                <button
                    type="button"
                    class="role-card js-role-card js-role-teacher"
                    data-role-value="'.$teacherValue.'"
                    aria-pressed="false"
                    '.$teacherDisabledAttr.'
                >
                    <span class="role-card__icon">'.$teacherIcon.'</span>
                    <span class="role-card__text">
                        <span class="role-card__title">'.get_lang('Teach courses').'</span>
                        '.(!empty($teacherHint) ? '<span class="role-card__subtitle">'.$teacherHint.'</span>' : '').'
                    </span>
                </button>
            </div>
        ');

        // Keep QuickForm group (required rule + real field). We'll hide it with CSS.
        $form->addRadio(
            'status',
            null,
            [
                $studentValue => get_lang('Follow courses'),
                $teacherValue => get_lang('Teach courses'),
            ],
            ['class' => 'register-profile']
        );

        $form->addRule('status', get_lang('Required field'), 'required');

        $htmlHeadXtra[] = <<<EOD
<style>
/* Card grid: 1 column on small screens, 2 columns on md+ */
.role-cards-grid{
  display:grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}
@media (min-width: 768px){
  .role-cards-grid{
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

/* Card styling */
.role-card{
  width:100%;
  display:flex;
  align-items:center;
  gap:1rem;
  padding:1rem;
  border:1px solid #E5E7EB;
  border-radius:1rem;
  background:#fff;
  box-shadow: 0 1px 2px rgba(0,0,0,.05);
  cursor:pointer;
  text-align:left;
  transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
}
.role-card:hover{ background:#F9FAFB; }
.role-card:disabled{ opacity:.5; cursor:not-allowed; }
.role-card__icon{
  width:48px; height:48px;
  border-radius:12px;
  background:#F3F4F6;
  display:flex;
  align-items:center;
  justify-content:center;
  flex:0 0 48px;
}
.role-card__text{ display:flex; flex-direction:column; gap:.125rem; }
.role-card__title{ font-weight:600; color:#111827; }
.role-card__subtitle{ font-size:.875rem; color:#6B7280; }

.role-card.is-selected{
  border-color:#6B7280;
  box-shadow: 0 0 0 2px rgba(107,114,128,.35);
  background:#F3F4F6;
}

/* Hard hide: remove the whole native QuickForm row that contains status radios (Chrome supports :has) */
form[name="registration"] tr:has(input[type="radio"][name="status"]),
form[name="registration"] tr:has(input[type="radio"][name^="status["]),
form[name="registration"] li:has(input[type="radio"][name="status"]),
form[name="registration"] li:has(input[type="radio"][name^="status["]){
  display:none !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const allowTeacher = !!window.CHAMILO_ALLOW_TEACHER_REGISTRATION;
  const teacherValue = {$teacherValueJs};

  const form = document.querySelector('form[name="registration"]');
  const cardsRoot = document.getElementById('role-cards');
  if (!form || !cardsRoot) return;

  const statusRadios = Array.from(form.querySelectorAll('input[type="radio"]')).filter(r => {
    const n = String(r.name || '');
    return n === 'status' || n.startsWith('status[');
  });
  if (!statusRadios.length) return;

  const cards = Array.from(cardsRoot.querySelectorAll('[data-role-value]'));

  function findRadioByValue(value) {
    return statusRadios.find(r => String(r.value) === String(value)) || null;
  }

  function hideNativeStatusUI() {
    statusRadios.forEach(r => {
      // Your generated HTML uses <div class="field"> as the wrapper for radios
      const wrapper =
        r.closest('.field') ||
        r.closest('.form-group') ||
        r.closest('.control-group') ||
        r.closest('.row') ||
        r.parentElement;

      if (wrapper && wrapper !== form && wrapper !== cardsRoot) {
        wrapper.style.display = 'none';
      }
    });
  }

  hideNativeStatusUI();

  // Enforce teacher disable at input level too (security)
  if (!allowTeacher) {
    const teacherRadio = findRadioByValue(teacherValue);
    if (teacherRadio) {
      teacherRadio.disabled = true;
      if (teacherRadio.checked) {
        const firstEnabled = statusRadios.find(r => !r.disabled);
        if (firstEnabled) firstEnabled.checked = true;
      }
    }
  }

  function setRole(value) {
    const radio = findRadioByValue(value);
    if (!radio || radio.disabled) return;

    radio.checked = true;
    radio.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function syncUI() {
    cards.forEach(card => {
      const value = card.getAttribute('data-role-value');
      const radio = findRadioByValue(value);

      const checked = !!(radio && radio.checked);
      const disabled = !!(radio && radio.disabled);

      card.classList.toggle('is-selected', checked);
      card.setAttribute('aria-pressed', checked ? 'true' : 'false');

      if (disabled) card.setAttribute('disabled', 'disabled');
      else card.removeAttribute('disabled');
    });
  }

  cards.forEach(card => {
    card.addEventListener('click', function (e) {
      e.preventDefault();
      setRole(card.getAttribute('data-role-value'));
    });
  });

  statusRadios.forEach(r => r.addEventListener('change', syncUI));

  syncUI();
});
</script>
EOD;
    }

    $emailLegacyRequired = ('true' === api_get_setting('registration', 'email'));
    $isEmailRequired = $emailLegacyRequired || $isEmailRequiredFromRequiredProfile;

    $isPhoneRequired = $hasRequiredProfileConfig ? $isPhoneRequiredFromRequiredProfile : true;
    $isOfficialCodeRequired = $hasRequiredProfileConfig ? $isOfficialCodeRequiredFromRequiredProfile : true;
    $isLanguageRequired = $hasRequiredProfileConfig ? $isLanguageRequiredFromRequiredProfile : false;

    // EMAIL
    $form->addElement('text', 'email', get_lang('E-mail'), ['size' => 40]);
    if ($isEmailRequired) {
        $form->addRule('email', get_lang('Required field'), 'required');
        $markRequired($form, 'email');
    }

    if ($isTccEnabled) {
        $form->addButtonSearch(get_lang('SearchTCC'), 'search', ['id' => 'search_user']);
    }

    $LastnameLabel = get_lang('Last name');
    if ('true' === api_get_setting('profile.registration_add_helptext_for_2_names')) {
        $LastnameLabel = [$LastnameLabel, get_lang('Insert your two names')];
    }
    if (api_is_western_name_order()) {
        // FIRST NAME and LAST NAME
        $form->addElement('text', 'firstname', get_lang('First name'), ['size' => 40]);
        $form->addElement('text', 'lastname', get_lang('Last name'), ['size' => 40]);
    } else {
        // LAST NAME and FIRST NAME
        $form->addElement('text', 'lastname', get_lang('Last name'), ['size' => 40]);
        $form->addElement('text', 'firstname', get_lang('First name'), ['size' => 40]);
    }
    $form->applyFilter(['lastname', 'firstname'], 'trim');
    $form->addRule('lastname', get_lang('Required field'), 'required');
    $form->addRule('firstname', get_lang('Required field'), 'required');

    if ('true' === api_get_setting('login_is_email')) {
        $form->applyFilter('email', 'trim');

        // Ensure email is required when used as login (legacy behavior), or when required_profile_fields asks for it.
        if (!$isEmailRequired) {
            $form->addRule('email', get_lang('Required field'), 'required');
            $markRequired($form, 'email');
            $isEmailRequired = true;
        }
        $form->addRule(
            'email',
            sprintf(
                get_lang('The login needs to be maximum %s characters long'),
                (string) User::USERNAME_MAX_LENGTH
            ),
            'maxlength',
            User::USERNAME_MAX_LENGTH
        );
        $form->addRule('email', get_lang('This login is already in use'), 'username_available');
    }

    $form->addEmailRule('email');

    $form->addRule(
        'email',
        get_lang('This e-mail address has already been used by the maximum number of allowed accounts. Please use another.'),
        'callback',
        function ($email) {
            return !api_email_reached_registration_limit($email);
        }
    );

    // USERNAME
    if ('true' != api_get_setting('login_is_email')) {
        $form->addText(
            'username',
            get_lang('Username'),
            true,
            [
                'id' => 'username',
                'size' => User::USERNAME_MAX_LENGTH,
                'autocomplete' => 'off',
            ]
        );
        $form->applyFilter('username', 'trim');
        $form->addRule('username', get_lang('Required field'), 'required');
        $form->addRule(
            'username',
            sprintf(
                get_lang('The login needs to be maximum %s characters long'),
                (string) User::USERNAME_MAX_LENGTH
            ),
            'maxlength',
            User::USERNAME_MAX_LENGTH
        );
        $form->addRule('username', get_lang('Your login can only contain letters, numbers and _.-'), 'username');
        $form->addRule('username', get_lang('This login is already in use'), 'username_available');
    }

    $passDiv = '<div id="password_progress"></div><div id="password-verdict"></div><div id="password-errors"></div>';

    $checkPass = api_get_setting('allow_strength_pass_checker');
    if ('true' === $checkPass) {
        $checkPass = '';
    }

    // PASSWORD
    $form->addElement(
        'password',
        'pass1',
        [get_lang('Pass'), $passDiv],
        ['id' => 'pass1', 'size' => 20, 'autocomplete' => 'off', 'show_hide' => true]
    );

    $checkPass = api_get_setting('allow_strength_pass_checker');

    $form->addElement(
        'password',
        'pass2',
        get_lang('Confirm password'),
        ['id' => 'pass2', 'size' => 20, 'autocomplete' => 'off']
    );
    $form->addRule('pass1', get_lang('Required field'), 'required');
    $form->addRule('pass2', get_lang('Required field'), 'required');
    $form->addRule(['pass1', 'pass2'], get_lang('You have typed two different passwords'), 'compare');
    $form->addPasswordRule('pass1');

    if ($checkPass) {
        $form->addRule(
            'pass1',
            get_lang('Password too easy to guess').': '.api_generate_password(),
            'callback',
            'api_check_password'
        );
    }

    // PHONE
    if (in_array('phone', $allowedFields, true)) {
        $form->addElement('text', 'phone', get_lang('Phone'), ['size' => 20]);
        if ($isPhoneRequired) {
            $form->addRule('phone', get_lang('Required field'), 'required');
            $markRequired($form, 'phone');
        }
    }

    // Language
    if (in_array('language', $allowedFields, true)) {
        $form->addSelectLanguage('language', get_lang('Language'), [], ['id' => 'language']);
        if ($isLanguageRequired) {
            $form->addRule('language', get_lang('Required field'), 'required');
            $markRequired($form, 'language');
        }
    }

    if (in_array('official_code', $allowedFields, true)) {
        $form->addElement('text', 'official_code', get_lang('Official code'), ['size' => 40]);
        if ($isOfficialCodeRequired) {
            $form->addRule('official_code', get_lang('Required field'), 'required');
            $markRequired($form, 'official_code');
        }
    }

    if (in_array('date_of_birth', $allowedFields, true)) {
        $form->addDatePicker('date_of_birth', get_lang('Date of birth'), ['required' => false]);
    }

    // EXTENDED FIELDS
    if ('true' === api_get_setting('extended_profile') &&
        'true' === api_get_setting('extendedprofile_registration', 'mycomptetences')
    ) {
        $form->addHtmlEditor(
            'competences',
            get_lang('My competences'),
            false,
            false,
            ['ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130']
        );
    }

    if ('true' === api_get_setting('extended_profile') &&
        'true' === api_get_setting('extendedprofile_registration', 'mydiplomas')
    ) {
        $form->addHtmlEditor(
            'diplomas',
            get_lang('My diplomas'),
            false,
            false,
            ['ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130']
        );
    }

    if ('true' === api_get_setting('extended_profile') &&
        'true' === api_get_setting('extendedprofile_registration', 'myteach')
    ) {
        $form->addHtmlEditor(
            'teach',
            get_lang('What I can teach'),
            false,
            false,
            ['ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130']
        );
    }

    if ('true' === api_get_setting('extended_profile') &&
        'true' === api_get_setting('extendedprofile_registration', 'mypersonalopenarea')
    ) {
        $form->addHtmlEditor(
            'openarea',
            get_lang('My personal open area'),
            false,
            false,
            ['ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130']
        );
    }

    if ('true' === api_get_setting('extended_profile')) {
        if ('true' === api_get_setting('extendedprofile_registration', 'mycomptetences') &&
            'true' === api_get_setting('extendedprofile_registrationrequired', 'mycomptetences')
        ) {
            $form->addRule('competences', get_lang('Required field'), 'required');
        }
        if ('true' === api_get_setting('extendedprofile_registration', 'mydiplomas') &&
            'true' === api_get_setting('extendedprofile_registrationrequired', 'mydiplomas')
        ) {
            $form->addRule('diplomas', get_lang('Required field'), 'required');
        }
        if ('true' === api_get_setting('extendedprofile_registration', 'myteach') &&
            'true' === api_get_setting('extendedprofile_registrationrequired', 'myteach')
        ) {
            $form->addRule('teach', get_lang('Required field'), 'required');
        }
        if ('true' === api_get_setting('extendedprofile_registration', 'mypersonalopenarea') &&
            'true' === api_get_setting('extendedprofile_registrationrequired', 'mypersonalopenarea')
        ) {
            $form->addRule('openarea', get_lang('Required field'), 'required');
        }
    }

    $form->addElement('hidden', 'extra_tcc_user_id');
    $form->addElement('hidden', 'extra_tcc_hash_key');

    // EXTRA FIELDS
    $settingRequiredFields = api_get_setting('registration.required_extra_fields_in_inscription', true);
    $requiredExtraFieldVars = $normalizeSettingList($settingRequiredFields);

    // Load extra fields if:
    // - extra fields are enabled in allow_fields_inscription OR
    // - there are required extra fields configured OR
    // - conditions extra fields exist (profile.show_conditions_to_user)
    $shouldLoadExtraFields = (
        array_key_exists('extra_fields', $allowedFields) ||
        in_array('extra_fields', $allowedFields, true) ||
        !empty($requiredExtraFieldVars) ||
        (!empty($extraConditions) && is_array($extraConditions))
    );

    if ($shouldLoadExtraFields) {
        $extraField = new ExtraField('user');

        // Extra fields that must NEVER be shown on the registration form.
        $registrationExtraFieldBlacklist = [
            'mail_notify_invitation',
            'mail_notify_message',
            'mail_notify_group_message',
        ];

        // Helper: determine if an extra field is user-visible and user-editable.
        $isFieldUserEditable = static function (array $info): bool {
            $visibleToSelf = (int) ($info['visible_to_self'] ?? 0);
            $changeable = (int) ($info['changeable'] ?? 0);

            return $visibleToSelf === 1 && $changeable === 1;
        };

        // Forced fields (conditions) must be displayed even if not editable.
        $forcedVars = [];
        if (!empty($extraConditions) && is_array($extraConditions)) {
            foreach ($extraConditions as $condition) {
                if (!empty($condition['variable'])) {
                    $forcedVars[] = (string) $condition['variable'];
                }
            }
        }

        $isBlacklisted = static function (string $var) use ($registrationExtraFieldBlacklist): bool {
            return $var === '' || in_array($var, $registrationExtraFieldBlacklist, true);
        };

        $fieldExists = static function ($extraField, string $var): ?array {
            $info = $extraField->get_handler_field_info_by_field_variable($var);
            return false === $info ? null : $info;
        };

        // Build the whitelist of extra fields allowed on registration.
        $extraFieldList = [];
        if (isset($allowedFields['extra_fields']) && is_array($allowedFields['extra_fields'])) {
            $extraFieldList = $allowedFields['extra_fields'];
        }

        // Always include required extra fields and forced condition fields.
        $extraFieldList = array_merge($extraFieldList, $requiredExtraFieldVars, $forcedVars);

        // Normalize + filter.
        $extraFieldList = array_values(array_unique(array_map(static function ($v) {
            return trim((string) $v);
        }, $extraFieldList)));

        $extraFieldList = array_values(array_filter($extraFieldList, static function ($var) use (
            $extraField,
            $isBlacklisted,
            $fieldExists,
            $isFieldUserEditable,
            $requiredExtraFieldVars,
            $forcedVars
        ) {
            if ($isBlacklisted($var)) {
                return false;
            }

            $info = $fieldExists($extraField, $var);
            if (null === $info) {
                return false;
            }

            // Required/forced fields must be shown (otherwise registration can become impossible).
            $isRequired = in_array($var, $requiredExtraFieldVars, true);
            $isForced = in_array($var, $forcedVars, true);

            if ($isRequired || $isForced) {
                return true;
            }

            // Optional fields: show only if user can see + modify them.
            return $isFieldUserEditable($info);
        }));

        // Required list must match what we actually display.
        $requiredFields = array_values(array_filter($requiredExtraFieldVars, static function ($var) use (
            $extraField,
            $isBlacklisted,
            $fieldExists
        ) {
            $var = trim((string) $var);
            if ($isBlacklisted($var)) {
                return false;
            }

            return null !== $fieldExists($extraField, $var);
        }));

        if (!empty($extraFieldList)) {
            $extraField->addElements(
                $form,
                0,
                [],
                false,
                false,
                $extraFieldList,
                [],
                [],
                false,
                false,
                [],
                [],
                false,
                [],
                $requiredFields,
                true
            );

            $extraFieldsLoaded = true;
        }
    }

    // CAPTCHA
    $captcha = api_get_setting('allow_captcha');
    $allowCaptcha = 'true' === $captcha;

    if ($allowCaptcha) {
        $ajax = api_get_path(WEB_AJAX_PATH).'form.ajax.php?a=get_captcha';
        $options = [
            'width' => 220,
            'height' => 90,
            'callback' => $ajax.'&var='.basename(__FILE__, '.php'),
            'sessionVar' => basename(__FILE__, '.php'),
            'imageOptions' => [
                'font_size' => 20,
                'font_path' => api_get_path(SYS_FONTS_PATH).'opensans/',
                'font_file' => 'OpenSans-Regular.ttf',
            ],
        ];

        $captcha_question = $form->addElement('CAPTCHA_Image', 'captcha_question', '', $options);
        $form->addElement('static', null, null, get_lang('Click on the image to load a new one.'));

        $form->addElement('text', 'captcha', get_lang('Enter the letters you see.'), ['size' => 40]);
        $form->addRule('captcha', get_lang('Enter the characters you see on the image'), 'required', null, 'client');
        $form->addRule('captcha', get_lang('The text you entered doesn\'t match the picture.'), 'CAPTCHA', $captcha_question);
    }
}

// Defaults
if (isset($_SESSION['user_language_choice']) && '' != $_SESSION['user_language_choice']) {
    $defaults['language'] = $_SESSION['user_language_choice'];
} else {
    $defaults['language'] = api_get_setting('platformLanguage');
}
if (!empty($_POST['language'])) {
    $defaults['language'] = Security::remove_XSS($_POST['language']);
}
if (!empty($_GET['username'])) {
    $defaults['username'] = Security::remove_XSS($_GET['username']);
}
if (!empty($_GET['email'])) {
    $defaults['email'] = Security::remove_XSS($_GET['email']);
}
if (!empty($_GET['phone'])) {
    $defaults['phone'] = Security::remove_XSS($_GET['phone']);
}
if ('true' === api_get_setting('openid_authentication') && !empty($_GET['openid'])) {
    $defaults['openid'] = Security::remove_XSS($_GET['openid']);
}

$defaults['status'] = STUDENT;
$defaults['extra_mail_notify_invitation'] = 1;
$defaults['extra_mail_notify_message'] = 1;
$defaults['extra_mail_notify_group_message'] = 1;

$form->applyFilter('__ALL__', 'Security::remove_XSS');
$form->setDefaults($defaults);
$content = null;

$user['language'] = 'french';
$userInfo = api_get_user_info();
if (!empty($userInfo)) {
    $langInfo = api_get_language_from_iso($userInfo['language']);
}

$toolName = get_lang('Registration');
if ('approval' === api_get_setting('allow_registration')) {
    $content .= Display::return_message(get_lang('Your account has to be approved'));
}

// if openid was not found
if (!empty($_GET['openid_msg']) && 'idnotfound' == $_GET['openid_msg']) {
    $content .= Display::return_message(get_lang('This OpenID could not be found in our database. Please register for a new account. If you have already an account with us, please edit your profile inside your account to add this OpenID'));
}

if ($extraConditions) {
    $form->addCheckBox('extra_platformuseconditions', null, get_lang('Platform use conditions'));
    $form->addRule('extra_platformuseconditions', get_lang('Required field'), 'required');
}

$blockButton = false;
$termActivated = false;
$showTerms = false;
$infoMessage = '';

if ($blockButton) {
    if (!empty($infoMessage)) {
        $form->addHtml($infoMessage);
    }
    $form->addButton(
        'submit',
        get_lang('Register'),
        'check',
        'primary',
        null,
        null,
        ['disabled' => 'disabled'],
        false
    );
} else {
    $allow = ('true' === api_get_setting('registration.allow_double_validation_in_registration'));

    ChamiloHelper::addLegalTermsFields($form, $userAlreadyRegisteredShowTerms);

    /**
     * Double validation must be controlled ONLY by:
     * registration.allow_double_validation_in_registration
     * It must not depend on Terms & Conditions activation.
     */
    if ($allow && !$termActivated) {
        $htmlHeadXtra[] = '<script>
            $(document).ready(function() {
                $("#pre_validation").click(function() {
                    $(this).hide();
                    $("#final_button").show();
                });
            });
        </script>';

        $form->addLabel(
            null,
            Display::url(get_lang('Validate'), 'javascript:void', ['class' => 'btn btn--plain', 'id' => 'pre_validation'])
        );
        $form->addHtml('<div id="final_button" style="display: none">');
        $form->addLabel(null, Display::return_message(get_lang('You confirm that you really want to subscribe to this platform.'), 'info', false));
        $form->addButton('submit', get_lang('Register'), '', 'primary');
        $form->addHtml('</div>');
    } else {
        $form->addButtonNext(get_lang('Register'));
    }
    $showTerms = true;
}

$courseIdRedirect = Session::read('course_redirect');
$sessionToRedirect = Session::read('session_redirect');

if ($extraConditions && $extraFieldsLoaded) {
    // Set conditions as "required" and also change the labels
    foreach ($extraConditions as $condition) {
        $name = 'extra_'.$condition['variable'];
        if (method_exists($form, 'elementExists') && !$form->elementExists($name)) {
            continue;
        }

        /** @var HTML_QuickForm_group $element */
        $element = $form->getElement($name);
        $children = $element->getElements();
        /** @var HTML_QuickForm_checkbox $child */
        foreach ($children as $child) {
            $child->setText(get_lang($condition['display_text']));
        }
        $form->setRequired($element);
        if (!empty($condition['text_area'])) {
            $element->setLabel(
                [
                    '',
                    '<div class="form-control" disabled=disabled style="height: 100px; overflow: auto;">'.
                    get_lang(nl2br($condition['text_area'])).
                    '</div>',
                ]
            );
        }
    }
}

$tpl = new Template($toolName);
$textAfterRegistration = '';
if ($form->validate()) {
    $values = $form->getSubmitValues(1);
    // Make *sure* the login isn't too long
    if (isset($values['username'])) {
        $values['username'] = api_substr($values['username'], 0, User::USERNAME_MAX_LENGTH);
    }

    // Security rule: if teacher registration is disabled, force learner status.
    if (!$allowTeacherRegistration) {
        $values['status'] = STUDENT;
    }

    if (empty($values['official_code']) && !empty($values['username'])) {
        $values['official_code'] = api_strtoupper($values['username']);
    }

    if ('true' === api_get_setting('login_is_email')) {
        $values['username'] = $values['email'];
    }

    // Register extra fields
    $extras = [];
    $extraParams = [];
    foreach ($values as $key => $value) {
        if ('extra_' === substr($key, 0, 6)) {
            $extras[substr($key, 6)] = $value;
            $extraParams[$key] = $value;
        }
    }

    $status = $values['status'] ?? STUDENT;
    $phone = $values['phone'] ?? null;
    $values['language'] = isset($values['language']) ? $values['language'] : api_get_language_isocode();
    $values['address'] = $values['address'] ?? '';

    // It gets a creator id when user is not logged.
    $creatorId = 0;
    if (api_is_anonymous()) {
        $adminList = UserManager::get_all_administrators();
        $creatorId = 1;
        if (!empty($adminList)) {
            $adminInfo = current($adminList);
            $creatorId = (int) $adminInfo['user_id'];
        }
    }

    // Creates a new user
    $userId = UserManager::create_user(
        $values['firstname'],
        $values['lastname'],
        (int) $status,
        $values['email'],
        $values['username'],
        $values['pass1'],
        $values['official_code'],
        $values['language'],
        $phone,
        null,
        [UserAuthSource::PLATFORM],
        null,
        1,
        0,
        $extraParams,
        null,
        true,
        false,
        $values['address'],
        true,
        $form,
        $creatorId
    );

    // Save T&C acceptance
    if ('true' === api_get_setting('allow_terms_conditions') && !empty($values['legal_accept_type'])) {
        ChamiloHelper::saveUserTermsAcceptance($userId, $values['legal_accept_type']);
    }

    // Update the extra fields
    $countExtraField = count($extras);
    if ($countExtraField > 0 && is_int($userId)) {
        foreach ($extras as $key => $value) {
            if (is_array($value)) {
                if (array_key_exists('tmp_name', $value) && empty($value['tmp_name'])) {
                    // Nothing to do.
                } else {
                    if (array_key_exists('tmp_name', $value)) {
                        $value['tmp_name'] = Security::filter_filename($value['tmp_name']);
                    }
                    if (array_key_exists('name', $value)) {
                        $value['name'] = Security::filter_filename($value['name']);
                    }
                    UserManager::update_extra_field_value($userId, $key, $value);
                }
            } else {
                UserManager::update_extra_field_value($userId, $key, $value);
            }
        }
    }

    if ($userId) {
        // Storing the extended profile
        $store_extended = false;
        $sql = "UPDATE ".Database::get_main_table(TABLE_MAIN_USER)." SET ";

        if ('true' == api_get_setting('extended_profile') && 'true' == api_get_setting('extendedprofile_registration', 'mycomptetences')) {
            $sql_set[] = "competences = '".Database::escape_string($values['competences'])."'";
            $store_extended = true;
        }

        if ('true' == api_get_setting('extended_profile') && 'true' == api_get_setting('extendedprofile_registration', 'mydiplomas')) {
            $sql_set[] = "diplomas = '".Database::escape_string($values['diplomas'])."'";
            $store_extended = true;
        }

        if ('true' == api_get_setting('extended_profile') && 'true' == api_get_setting('extendedprofile_registration', 'myteach')) {
            $sql_set[] = "teach = '".Database::escape_string($values['teach'])."'";
            $store_extended = true;
        }

        if ('true' == api_get_setting('extended_profile') && 'true' == api_get_setting('extendedprofile_registration', 'mypersonalopenarea')) {
            $sql_set[] = "openarea = '".Database::escape_string($values['openarea'])."'";
            $store_extended = true;
        }

        if ($store_extended) {
            $sql .= implode(',', $sql_set);
            $sql .= " WHERE user_id = ".intval($userId)."";
            Database::query($sql);
        }

        // Saving user to Session if it was set
        if (!empty($sessionToRedirect) && !$sessionPremiumChecker) {
            $sessionInfo = api_get_session_info($sessionToRedirect);
            if (!empty($sessionInfo)) {
                SessionManager::subscribeUsersToSession(
                    $sessionToRedirect,
                    [$userId],
                    SESSION_VISIBLE_READ_ONLY,
                    false
                );
            }
        }

        // Saving user to course if it was set.
        if (!empty($courseIdRedirect)) {
            $course_info = api_get_course_info_by_id($courseIdRedirect);
            if (!empty($course_info)) {
                if (in_array(
                    $course_info['visibility'],
                    [
                        COURSE_VISIBILITY_OPEN_PLATFORM,
                        COURSE_VISIBILITY_OPEN_WORLD,
                    ]
                )
                ) {
                    CourseManager::subscribeUser(
                        $userId,
                        $courseIdRedirect
                    );
                }
            }
        }

        /* If the account has to be approved, then we set the account to inactive,
        sent a mail to the platform admin and exit the page.*/
        if ('approval' === api_get_setting('allow_registration')) {
            // 1. Send mail to all platform admin
            $chamiloUser = api_get_user_entity($userId);
            MessageManager::sendNotificationOfNewRegisteredUserApproval($chamiloUser);

            // 2. set account inactive
            UserManager::disable($userId);

            // 3. exit the page
            unset($userId);

            Display::display_header($toolName);
            echo Display::page_header($toolName);
            echo $content;
            Display::display_footer();
            exit;
        } elseif ('confirmation' === api_get_setting('allow_registration')) {
            // 1. Send mail to the user
            $thisUser = api_get_user_entity($userId);
            UserManager::sendUserConfirmationMail($thisUser);

            // 2. set account inactive
            UserManager::disable($userId);

            // 3. exit the page
            unset($userId);

            Display::addFlash(
                Display::return_message(
                    get_lang('You need to confirm your account via e-mail to access the platform'),
                    'warning'
                )
            );

            Display::display_header($toolName);
            //echo $content;
            Display::display_footer();
            exit;
        }
    }

    /* SESSION REGISTERING */
    /* @todo move this in a function */
    $user['firstName'] = stripslashes($values['firstname']);
    $user['lastName'] = stripslashes($values['lastname']);
    $user['mail'] = $values['email'];
    $user['language'] = $values['language'];
    $user['user_id'] = $userId;
    $user['id'] = $userId;
    Session::write('_user', $user);

    $is_allowedCreateCourse = isset($values['status']) && 1 == $values['status'];
    $usersCanCreateCourse = api_is_allowed_to_create_course();

    Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);

    if ('AppCache' == get_class($kernel)) {
        $kernel = $kernel->getKernel();
    }
    /** @var ContainerInterface $container */
    $container = $kernel->getContainer();
    $entityManager = $container->get('doctrine.orm.default_entity_manager');
    $userRepository = $entityManager->getRepository(User::class);
    $userEntity = $userRepository->find($userId);

    $providerKey = 'main';
    $roles = $userEntity->getRoles();
    $token = new UsernamePasswordToken($userEntity, $providerKey, $roles);

    $container->get(ContainerHelper::class)->getTokenStorage()->setToken($token);
    $request = $container->get('request_stack')->getMainRequest();
    $sessionHandler = $container->get('request_stack')->getSession();
    $sessionHandler->set('_security_' . $providerKey, serialize($token));
    $userData = [
        'firstName' => stripslashes($values['firstname']),
        'lastName' => stripslashes($values['lastname']),
        'mail' => $values['email'],
        'language' => $values['language'],
        'user_id' => $userId,
        'id' => $userId,
    ];

    $sessionHandler->set('_user', $userData);
    $sessionHandler->set('_locale_user', $userEntity->getLocale());
    $sessionHandler->set('is_allowedCreateCourse', $is_allowedCreateCourse);

    // Stats
    Container::getTrackELoginRepository()->createLoginRecord($userEntity, new DateTime(), $request->getClientIp());

    /**
     * Direct link redirect (course + optional exercise).
     */
    $directCourseId = (int) Session::read('course_redirect');
    $directExerciseId = (int) Session::read('exercise_redirect');

    if ($directCourseId > 0) {
        Session::erase('course_redirect');
        Session::erase('exercise_redirect');

        $courseInfo = api_get_course_info_by_id($directCourseId);
        $visibility = (int) ($courseInfo['visibility'] ?? -1);

        $isOpenCourse = in_array(
            $visibility,
            [COURSE_VISIBILITY_OPEN_PLATFORM, COURSE_VISIBILITY_OPEN_WORLD],
            true
        );

        if ($isOpenCourse) {
            // Only for exercises: helps tracking, but must not gate redirect.
            if ($directExerciseId > 0) {
                CourseManager::autoSubscribeToCourse($directCourseId);
            }

            header('Location: ' . $buildDirectLinkRedirectUrl($directCourseId, $directExerciseId));
            exit;
        }

        header('Location: ' . api_get_path(WEB_PATH) . 'course/' . $directCourseId . '/about');
        exit;
    }

    // last user login date is now
    $user_last_login_datetime = 0; // used as a unix timestamp it will correspond to : 1 1 1970
    Session::write('user_last_login_datetime', $user_last_login_datetime);
    $recipient_name = api_get_person_name($values['firstname'], $values['lastname']);
    $textAfterRegistration =
        '<p>'.
        get_lang('Dear', $userEntity->getLocale()).' '.
        stripslashes(Security::remove_XSS($recipient_name)).',<br /><br />'.
        get_lang('Your personal settings have been registered', $userEntity->getLocale())."</p>";

    $formData = [
        'button' => Display::button(
            'next',
            get_lang('Next'),
            ['class' => 'btn btn--primary btn-large']
        ),
        'message' => '',
        'action' => api_get_path(WEB_PATH).'home',
        'go_button' => '',
    ];

    if ('true' === api_get_setting('allow_terms_conditions') && $userAlreadyRegisteredShowTerms) {
        if ('login' === api_get_setting('workflows.load_term_conditions_section')) {
            header('Location: /home');
            exit;
            //$formData['action'] = api_get_path(WEB_PATH).'user_portal.php';
        } else {
            $courseInfo = api_get_course_info();
            if (!empty($courseInfo)) {
                $formData['action'] = $courseInfo['course_public_url'].'?id_session='.api_get_session_id();
                $cidReset = true;
                Session::erase('_course');
                Session::erase('_cid');
            } else {
                $formData['action'] = api_get_path(WEB_PATH).'home';
            }
        }
    } else {
        if (!empty($values['email'])) {
            $linkDiagnostic = api_get_path(WEB_PATH).'main/search/search.php';
            $textAfterRegistration .= '<p>'.get_lang('An e-mail has been sent to remind you of your login and password', $userEntity->getLocale()).'</p>';
            $diagnosticPath = '<a href="'.$linkDiagnostic.'" class="custom-link">'.$linkDiagnostic.'</a>';
            $textAfterRegistration .= '<p>';
            if ('true' === api_get_setting('session.allow_search_diagnostic')) {
                $textAfterRegistration .= sprintf(
                    get_lang('Welcome, please go to diagnostic at %s.', $userEntity->getLocale()),
                    $diagnosticPath
                );
            }
            $textAfterRegistration .= '</p>';
        }

        if ($is_allowedCreateCourse) {
            if ($usersCanCreateCourse) {
                $formData['message'] = '<p>'.get_lang('You can now create your course').'</p>';
            }
            $formData['action'] = api_get_path(WEB_CODE_PATH).'create_course/add_course.php';

            if ('true' === api_get_setting('course_validation')) {
                $formData['button'] = Display::button(
                    'next',
                    get_lang('Create a course request'),
                    ['class' => 'btn btn--primary btn-large']
                );
            } else {
                $formData['button'] = Display::button(
                    'next',
                    get_lang('Create a course'),
                    ['class' => 'btn btn--primary btn-large']
                );
                $formData['go_button'] = '&nbsp;&nbsp;<a href="'.api_get_path(WEB_PATH).'index.php'.'">'.
                    Display::span(
                        get_lang('Next'),
                        ['class' => 'btn btn--primary btn-large']
                    ).'</a>';
            }
        } else {
            if ('true' == api_get_setting('catalog.allow_students_to_browse_courses')) {
                $formData['action'] = 'courses.php?action=subscribe';
                $formData['message'] = '<p>'.get_lang('You can now select, in the list, the course you want access to').".</p>";
            } else {
                $formData['action'] = api_get_path(WEB_PATH).'user_portal.php';
            }
            $formData['button'] = Display::button(
                'next',
                get_lang('Next'),
                ['class' => 'btn btn--primary btn-large']
            );
        }
    }

    if ($sessionPremiumChecker && $sessionId) {
        Session::erase('SessionIsPremium');
        Session::erase('sessionId');
        header('Location:'.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/process.php?i='.$sessionId.'&t=2');
        exit;
    }

    SessionManager::redirectToSession();

    $redirectBuyCourse = Session::read('buy_course_redirect');
    if (!empty($redirectBuyCourse)) {
        $formData['action'] = api_get_path(WEB_PATH).$redirectBuyCourse;
        Session::erase('buy_course_redirect');
    }

    $formData = CourseManager::redirectToCourse($formData);
    $formRegister = new FormValidator('form_register', 'post', $formData['action']);
    if (!empty($formData['message'])) {
        $formRegister->addElement('html', $formData['message'].'<br /><br />');
    }

    if ($usersCanCreateCourse) {
        $formRegister->addElement('html', $formData['button']);
    } else {
        if (!empty($redirectBuyCourse)) {
            $formRegister->addButtonNext(get_lang('Next'));
        } else {
            $formRegister->addElement('html', $formData['go_button']);
        }
    }

    $textAfterRegistration .= $formRegister->returnForm();

    // Just in case
    Session::erase('course_redirect');
    Session::erase('exercise_redirect');
    Session::erase('session_redirect');
    Session::erase('only_one_course_session_redirect');
    Session::write('textAfterRegistration', $textAfterRegistration);

    header('location: '.api_get_self());
    exit;

} else {
    $textAfterRegistration = Session::read('textAfterRegistration');
    if (isset($textAfterRegistration)) {
        $tpl->assign('inscription_header', Display::page_header($toolName));
        $tpl->assign('inscription_content', $content);
        $tpl->assign('form', '');
        $tpl->assign('text_after_registration', $textAfterRegistration);
        $tpl->assign('hide_header', $hideHeaders);
        $inscription = $tpl->get_template('auth/inscription.tpl');
        $tpl->display($inscription);

        Session::erase('textAfterRegistration');
    } else {
        if (!api_is_anonymous()) {
            // Saving user to course if it was set.
            if (!empty($courseIdRedirect)) {
                $course_info = api_get_course_info_by_id($courseIdRedirect);
                if (!empty($course_info)) {
                    if (in_array(
                        $course_info['visibility'],
                        [
                            COURSE_VISIBILITY_OPEN_PLATFORM,
                            COURSE_VISIBILITY_OPEN_WORLD,
                        ]
                    )
                    ) {
                        CourseManager::subscribeUser(
                            api_get_user_id(),
                            $courseIdRedirect
                        );
                    }
                }
            }
            CourseManager::redirectToCourse([]);
        }

        $inscriptionHeader = '';
        if (false !== $termActivated) {
            $inscriptionHeader = Display::page_header($toolName);
        }
        $em = Container::getEntityManager();
        $categoryRepo = $em->getRepository(PageCategory::class);
        $pageRepo = $em->getRepository(Page::class);
        $accessUrl = api_get_url_entity();
        $locale = api_get_language_isocode();

        $category = $categoryRepo->findOneBy(['title' => 'introduction']);
        $introPage = null;
        if ($category) {
            $introPage = $pageRepo->findOneBy([
                'category' => $category,
                'url' => $accessUrl,
                'enabled' => true,
            ]);
        }

        if ($introPage) {
            // Tailwind-styled info box (keeps content intact).
            $content = '<div class="mb-4 rounded-2xl border border-gray-25 bg-gray-15 p-4 text-gray-90 shadow-sm">'
                . $introPage->getContent()
                . '</div>' . $content;
        }

        if ($isCreatingIntroPage && $isPlatformAdmin) {
            $user = api_get_user_entity();

            if ($introPage) {
                header('Location: '.api_get_path(WEB_PATH).'resources/pages/edit?id=/api/pages/'.$introPage->getId());
                exit;
            }

            if (!$category) {
                $category = new PageCategory();
                $category
                    ->setTitle('introduction')
                    ->setType('cms')
                    ->setCreator($user);
                $em->persist($category);
                $em->flush();
            }

            $page = new Page();
            $page
                ->setTitle(get_lang("Introduction to registration"))
                ->setContent('<p>'.get_lang("Welcome to the registration process.").'</p>')
                ->setSlug('intro-inscription')
                ->setLocale($locale)
                ->setCategory($category)
                ->setEnabled(true)
                ->setCreator($user)
                ->setUrl($accessUrl)
                ->setPosition(1);

            $em->persist($page);
            $em->flush();

            header('Location: '.api_get_path(WEB_PATH).'resources/pages/edit?id=/api/pages/'.$page->getId());
            exit;
        }
        $tpl->assign('inscription_header', $inscriptionHeader);
        $tpl->assign('inscription_content', $content);
        $tpl->assign('form', $form->returnForm());
        $tpl->assign('hide_header', $hideHeaders);
        $tpl->assign('text_after_registration', $textAfterRegistration);
        $inscription = $tpl->get_template('auth/inscription.tpl');
        $tpl->display($inscription);
    }
}
