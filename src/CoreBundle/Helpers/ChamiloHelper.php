<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use ChamiloSession as Session;
use Database;
use DateInterval;
use DateTime;
use DateTimeZone;
use Display;
use DocumentManager;
use Event;
use Exception;
use ExtraFieldValue;
use FormValidator;
use LegalManager;
use MessageManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Template;
use Throwable;
use UserManager;

use const ENT_HTML5;
use const ENT_QUOTES;
use const PATHINFO_EXTENSION;
use const PHP_ROUND_HALF_UP;
use const PHP_SAPI;
use const PHP_URL_PATH;

class ChamiloHelper
{
    public const COURSE_MANAGER = 1;
    public const SESSION_ADMIN = 3;
    public const DRH = 4;
    public const STUDENT = 5;
    public const ANONYMOUS = 6;

    private static array $configuration;

    public function setConfiguration(array $configuration): void
    {
        self::$configuration = $configuration;
    }

    public static function getConfigurationArray(): array
    {
        return self::$configuration;
    }

    public static function getConfigurationValue(string $variable): mixed
    {
        $configuration = self::getConfigurationArray();
        if (\array_key_exists($variable, $configuration)) {
            return $configuration[$variable];
        }

        return false;
    }

    /**
     * Returns an array of resolutions that can be used for the conversion of documents to images.
     */
    public static function getDocumentConversionSizes(): array
    {
        return [
            '540x405' => '540x405 (3/4)',
            '640x480' => '640x480 (3/4)',
            '720x540' => '720x540 (3/4)',
            '800x600' => '800x600 (3/4)',
            '1024x576' => '1024x576 (16/9)',
            '1024x768' => '1000x750 (3/4)',
            '1280x720' => '1280x720 (16/9)',
            '1280x860' => '1280x960 (3/4)',
            '1400x1050' => '1400x1050 (3/4)',
            '1600x900' => '1600x900 (16/9)',
        ];
    }

    /**
     * Get the platform logo path.
     *
     * @deprecated
     *
     * @throws Exception
     */
    public static function getPlatformLogoPath(
        string $theme = '',
        bool $getSysPath = false,
        bool $forcedGetter = false
    ): ?string {
        static $logoPath;

        // If call from CLI it should be reloaded.
        if ('cli' === PHP_SAPI) {
            $logoPath = null;
        }

        if (!isset($logoPath) || $forcedGetter) {
            $theme = empty($theme) ? api_get_visual_theme() : $theme;
            $accessUrlId = api_get_current_access_url_id();
            if ('cli' === PHP_SAPI) {
                $accessUrl = api_get_configuration_value('access_url');
                if (!empty($accessUrl)) {
                    $accessUrlId = $accessUrl;
                }
            }
            $themeDir = Template::getThemeDir($theme);
            $customLogoPath = $themeDir.\sprintf('images/header-logo-custom%s.png', $accessUrlId);
            $customLogoPathSVG = substr($customLogoPath, 0, -3).'svg';
            if (file_exists(api_get_path(SYS_PUBLIC_PATH).\sprintf('css/%s', $customLogoPathSVG))) {
                if ($getSysPath) {
                    return api_get_path(SYS_PUBLIC_PATH).\sprintf('css/%s', $customLogoPathSVG);
                }

                return api_get_path(WEB_CSS_PATH).$customLogoPathSVG;
            }
            if (file_exists(api_get_path(SYS_PUBLIC_PATH).\sprintf('css/%s', $customLogoPath))) {
                if ($getSysPath) {
                    return api_get_path(SYS_PUBLIC_PATH).\sprintf('css/%s', $customLogoPath);
                }

                return api_get_path(WEB_CSS_PATH).$customLogoPath;
            }

            $originalLogoPath = $themeDir.'images/header-logo.png';
            $originalLogoPathSVG = $themeDir.'images/header-logo.svg';
            if (file_exists(api_get_path(SYS_CSS_PATH).$originalLogoPathSVG)) {
                if ($getSysPath) {
                    return api_get_path(SYS_CSS_PATH).$originalLogoPathSVG;
                }

                return api_get_path(WEB_CSS_PATH).$originalLogoPathSVG;
            }

            if (file_exists(api_get_path(SYS_CSS_PATH).$originalLogoPath)) {
                if ($getSysPath) {
                    return api_get_path(SYS_CSS_PATH).$originalLogoPath;
                }

                return api_get_path(WEB_CSS_PATH).$originalLogoPath;
            }
            $logoPath = '';
        }

        return $logoPath;
    }

    /**
     * Get the platform logo.
     * Return a <img> if the logo image exists.
     * Otherwise, return a <h2> with the institution name.
     *
     * @throws Exception
     */
    public static function getPlatformLogo(
        string $theme = '',
        array $imageAttributes = [],
        bool $getSysPath = false,
        bool $forcedGetter = false
    ): string {
        $logoPath = Container::getThemeHelper()->getThemeAssetUrl('images/header-logo.svg');

        if (empty($logoPath)) {
            $logoPath = Container::getThemeHelper()->getThemeAssetUrl('images/header-logo.png');
        }

        $institution = api_get_setting('Institution');
        $institutionUrl = api_get_setting('InstitutionUrl');
        $siteName = api_get_setting('siteName');

        if (null === $logoPath) {
            $headerLogo = Display::url($siteName, api_get_path(WEB_PATH).'index.php');

            if (!empty($institutionUrl) && !empty($institution)) {
                $headerLogo .= ' - '.Display::url($institution, $institutionUrl);
            }

            $courseInfo = api_get_course_info();
            if (isset($courseInfo['extLink']) && !empty($courseInfo['extLink']['name'])) {
                $headerLogo .= '<span class="extLinkSeparator"> - </span>';

                if (!empty($courseInfo['extLink']['url'])) {
                    $headerLogo .= Display::url(
                        $courseInfo['extLink']['name'],
                        $courseInfo['extLink']['url'],
                        [
                            'class' => 'extLink',
                        ]
                    );
                } elseif (!empty($courseInfo['extLink']['url'])) {
                    $headerLogo .= $courseInfo['extLink']['url'];
                }
            }

            return Display::tag('h2', $headerLogo, [
                'class' => 'text-left',
            ]);
        }

        $image = Display::img($logoPath, $institution, $imageAttributes);

        return Display::url($image, api_get_path(WEB_PATH).'index.php');
    }

    /**
     * Like strip_tags(), but leaves an additional space and removes only the given tags.
     *
     * @param array $tags Tags to be removed
     *
     * @return string The original string without the given tags
     */
    public static function stripGivenTags(string $string, array $tags): string
    {
        foreach ($tags as $tag) {
            $string2 = preg_replace('#</\b'.$tag.'\b[^>]*>#i', ' ', $string);
            if ($string2 !== $string) {
                $string = preg_replace('/<\b'.$tag.'\b[^>]*>/i', ' ', $string2);
            }
        }

        return $string;
    }

    /**
     * Adds or Subtract a time in hh:mm:ss to a datetime.
     *
     * @param string $time      Time to add or subtract in hh:mm:ss format
     * @param string $datetime  Datetime to be modified as accepted by the Datetime class constructor
     * @param bool   $operation True for Add, False to Subtract
     *
     * @throws Exception
     */
    public static function addOrSubTimeToDateTime(
        string $time,
        string $datetime = 'now',
        bool $operation = true
    ): string {
        $date = new DateTime($datetime);
        $hours = 0;
        $minutes = 0;
        $seconds = 0;
        sscanf($time, '%d:%d:%d', $hours, $minutes, $seconds);
        $timeSeconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
        if ($operation) {
            $date->add(new DateInterval('PT'.$timeSeconds.'S'));
        } else {
            $date->sub(new DateInterval('PT'.$timeSeconds.'S'));
        }

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Returns the course id (integer) for the given course directory or the current ID if no directory is defined.
     *
     * @param string|null $directory The course directory/path that appears in the URL
     *
     * @throws Exception
     */
    public static function getCourseIdByDirectory(?string $directory = null): int
    {
        if (!empty($directory)) {
            $directory = Database::escape_string($directory);
            $row = Database::select(
                'id',
                Database::get_main_table(TABLE_MAIN_COURSE),
                [
                    'where' => [
                        'directory = ?' => [$directory],
                    ],
                ],
                'first'
            );

            if (\is_array($row) && isset($row['id'])) {
                return $row['id'];
            }

            return 0;
        }

        return (int) Session::read('_real_cid', 0);
    }

    /**
     * Check if the current HTTP request is by AJAX.
     */
    public static function isAjaxRequest(): bool
    {
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;

        return 'XMLHttpRequest' === $requestedWith;
    }

    /**
     * Get a variable name for language file from a text.
     */
    public static function getLanguageVar(string $text, string $prefix = ''): string
    {
        $text = api_replace_dangerous_char($text);
        $text = str_replace(['-', ' ', '.'], '_', $text);
        $text = preg_replace('/_+/', '_', $text);
        // $text = str_replace('_', '', $text);
        $text = api_underscore_to_camel_case($text);

        return $prefix.$text;
    }

    /**
     * Get the stylesheet path for HTML blocks created with CKEditor.
     */
    public static function getEditorBlockStylePath(): string
    {
        $visualTheme = api_get_visual_theme();

        $cssFile = api_get_path(SYS_CSS_PATH).\sprintf('themes/%s/editor_content.css', $visualTheme);

        if (is_file($cssFile)) {
            return api_get_path(WEB_CSS_PATH).\sprintf('themes/%s/editor_content.css', $visualTheme);
        }

        return api_get_path(WEB_CSS_PATH).'editor_content.css';
    }

    /**
     * Get a list of colors from the palette at main/palette/pchart/default.color
     * and return it as an array of strings.
     *
     * @param bool     $decimalOpacity Whether to return the opacity as 0..100 or 0..1
     * @param bool     $wrapInRGBA     Whether to return it as 1,1,1,100 or rgba(1,1,1,100)
     * @param int|null $fillUpTo       If the number of colors is smaller than this number, generate more colors
     *
     * @return array An array of string colors
     */
    public static function getColorPalette(
        bool $decimalOpacity = false,
        bool $wrapInRGBA = false,
        ?int $fillUpTo = null
    ): array {
        // Get the common colors from the palette used for pchart
        $paletteFile = api_get_path(SYS_CODE_PATH).'palettes/pchart/default.color';
        $palette = file($paletteFile);
        if ($decimalOpacity) {
            // Because the pchart palette has transparency as integer values
            // (0..100) and chartjs uses percentage (0.0..1.0), we need to divide
            // the last value by 100, which is a bit overboard for just one chart
            foreach ($palette as $index => $color) {
                $components = explode(',', trim($color));
                $components[3] = round((int) $components[3] / 100, 1, PHP_ROUND_HALF_UP);
                $palette[$index] = implode(',', $components);
            }
        }
        if ($wrapInRGBA) {
            foreach ($palette as $index => $color) {
                $color = trim($color);
                $palette[$index] = 'rgba('.$color.')';
            }
        }
        // If we want more colors, loop through existing colors
        $count = \count($palette);
        if (isset($fillUpTo) && $fillUpTo > $count) {
            for ($i = $count; $i < $fillUpTo; $i++) {
                $palette[$i] = $palette[$i % $count];
            }
        }

        return $palette;
    }

    /**
     * Get the local time for the midnight.
     *
     * @param null|string $utcTime Optional. The time to ve converted.
     *                             See api_get_local_time.
     *
     * @throws Exception
     */
    public static function getServerMidnightTime(?string $utcTime = null): DateTime
    {
        // UTC -> local time string (already converted using resolved timezone)
        $localTime = DateTimeHelper::localTimeYmdHis($utcTime, null, 'UTC');

        // Resolved timezone (platform/user)
        $tz = DateTimeHelper::resolveTimezone();

        $localMidnight = new DateTime($localTime, new DateTimeZone($tz));
        $localMidnight->modify('midnight');

        return $localMidnight;
    }

    /**
     * Get JavaScript code necessary to load quiz markers-rolls in medialement's Markers Rolls plugin.
     */
    public static function getQuizMarkersRollsJS(): string
    {
        $webCodePath = api_get_path(WEB_CODE_PATH);
        $cidReq = api_get_cidreq(true, true, 'embeddable');
        $colorPalette = self::getColorPalette(false, true);

        return "
            var \$originalNode = $(originalNode),
                    qMarkersRolls = \$originalNode.data('q-markersrolls') || [],
                    qMarkersColor = \$originalNode.data('q-markersrolls-color') || '$colorPalette[0]';

                if (0 == qMarkersRolls.length) {
                    return;
                }

                instance.options.markersRollsColor = qMarkersColor;
                instance.options.markersRollsWidth = 2;
                instance.options.markersRolls = {};

                qMarkersRolls.forEach(function (qMarkerRoll) {
                    var url = '{$webCodePath}exercise/exercise_submit.php?$cidReq&'
                        + $.param({
                            exerciseId: qMarkerRoll[1],
                            learnpath_id: 0,
                            learnpath_item_id: 0,
                            learnpath_item_view_id: 0
                        });

                    instance.options.markersRolls[qMarkerRoll[0]] = url;
                });

                instance.buildmarkersrolls(instance, instance.controls, instance.layers, instance.media);
        ";
    }

    /**
     * Performs a redirection to the specified URL.
     *
     * This method sends a direct HTTP Location header to the client,
     * causing the browser to navigate to the specified URL. It should be
     * used with caution and only in scenarios where Symfony's standard
     * response handling is not applicable. The method terminates script
     * execution after sending the header.
     */
    public static function redirectTo(string $url): void
    {
        if (!empty($url)) {
            header("Location: $url");

            exit;
        }
    }

    /**
     * Checks if the current user has accepted the Terms & Conditions.
     */
    public static function userHasAcceptedTerms(): bool
    {
        $termRegistered = Session::read('term_and_condition');

        return isset($termRegistered['user_id']);
    }

    /**
     * Redirects to the Terms and Conditions page.
     */
    public static function redirectToTermsAndConditions(): void
    {
        $url = self::getTermsAndConditionsUrl();
        self::redirectTo($url);
    }

    /**
     * Returns the URL of the Terms and Conditions page.
     */
    public static function getTermsAndConditionsUrl(): string
    {
        return api_get_path(WEB_PATH).'main/auth/tc.php';
    }

    /**
     * Returns the URL of the Registration page.
     */
    public static function getRegistrationUrl(): string
    {
        return api_get_path(WEB_PATH).'main/auth/registration.php';
    }

    /**
     * Adds legal terms acceptance fields into a registration form.
     */
    public static function addLegalTermsFields(FormValidator $form, bool $userAlreadyRegisteredShowTerms): void
    {
        if ('true' !== api_get_setting('allow_terms_conditions') || $userAlreadyRegisteredShowTerms) {
            return;
        }

        $languageIso = api_get_language_isocode();
        $languageId = api_get_language_id($languageIso);
        $termPreview = LegalManager::get_last_condition($languageId);

        if (!$termPreview) {
            $defaultIso = (string) api_get_setting('language.platform_language');
            if ('' === $defaultIso || 'false' === $defaultIso) {
                $defaultIso = (string) api_get_setting('platformLanguage');
            }

            $defaultLangId = api_get_language_id($defaultIso);
            if ($defaultLangId > 0 && $defaultLangId !== $languageId) {
                $languageId = $defaultLangId;
                $termPreview = LegalManager::get_last_condition($languageId);
            }
        }

        if (!$termPreview) {
            return; // Still nothing -> show nothing
        }

        $version = (int) ($termPreview['version'] ?? 0);
        $langId = (int) ($termPreview['language_id'] ?? $languageId);

        // Track acceptance context
        $form->addElement('hidden', 'legal_accept_type', $version.':'.$langId);
        $form->addElement('hidden', 'legal_info', ((int) ($termPreview['id'] ?? 0)).':'.$langId);

        // Fetch ALL legal records for the same version + language (includes GDPR sections now)
        $table = Database::get_main_table(TABLE_MAIN_LEGAL);

        $rows = Database::select(
            '*',
            $table,
            [
                'where' => [
                    'language_id = ? AND version = ?' => [$langId, $version],
                ],
                'order' => 'type ASC, id ASC',
            ]
        );

        if (!\is_array($rows) || empty($rows)) {
            return;
        }

        // GDPR section titles indexed by "type" (1..15).
        $getSectionTitle = static function (int $type): string {
            $map = [
                1 => 'Personal data collection',
                2 => 'Personal data recording',
                3 => 'Personal data organization',
                4 => 'Personal data structure',
                5 => 'Personal data conservation',
                6 => 'Personal data adaptation or modification',
                7 => 'Personal data extraction',
                8 => 'Personal data queries',
                9 => 'Personal data use',
                10 => 'Personal data communication and sharing',
                11 => 'Personal data interconnection',
                12 => 'Personal data limitation',
                13 => 'Personal data deletion',
                14 => 'Personal data destruction',
                15 => 'Personal data profiling',
            ];

            return $map[$type] ?? '';
        };

        $fullHtml = '';

        foreach ($rows as $row) {
            $content = trim((string) ($row['content'] ?? ''));
            if ('' === $content) {
                continue;
            }

            $type = (int) ($row['type'] ?? 0);
            $dbTitle = trim((string) ($row['title'] ?? ($row['name'] ?? '')));
            $title = '' !== $dbTitle ? $dbTitle : $getSectionTitle($type);

            $fullHtml .= '<div class="mt-4">';

            if ('' !== $title) {
                $fullHtml .= '<h4 class="text-base font-semibold text-gray-90">'
                    .htmlspecialchars($title, ENT_QUOTES | ENT_HTML5)
                    .'</h4>';
            }

            // Content may contain HTML (kept as-is by design).
            $fullHtml .= '<div class="mt-2 text-sm text-gray-90">'.$content.'</div>';
            $fullHtml .= '</div>';
        }

        if ('' === trim(strip_tags($fullHtml))) {
            // Nothing meaningful to show
            return;
        }

        // Render the whole block at the bottom
        $form->addHtml('
        <div class="mt-6">
            <div class="text-lg font-semibold text-gray-90 mb-2">'.get_lang('Terms and Conditions').'</div>
            <div class="bg-gray-15 border border-gray-25 rounded-xl p-4 max-h-72 overflow-y-auto shadow-sm">
                '.$fullHtml.'
            </div>
        </div>
    ');

        // Acceptance checkbox (or hidden accept if configured)
        $hideAccept = 'true' === api_get_setting('registration.hide_legal_accept_checkbox');
        if ($hideAccept) {
            $form->addElement('hidden', 'legal_accept', '1');
        } else {
            $form->addElement(
                'checkbox',
                'legal_accept',
                null,
                'I have read and agree to the <a href="tc.php" target="_blank" rel="noopener noreferrer">Terms and Conditions</a>'
            );
            $form->addRule('legal_accept', 'This field is required', 'required');
        }
    }

    /**
     * Persists the user's acceptance of the terms & conditions.
     *
     * @param string $legalAcceptType version:language_id
     */
    public static function saveUserTermsAcceptance(int $userId, string $legalAcceptType): void
    {
        // Split and build the stored value
        [$version, $languageId] = explode(':', $legalAcceptType);
        $timestamp = time();
        $toSave = (int) $version.':'.(int) $languageId.':'.$timestamp;

        // Save in extra-field
        UserManager::update_extra_field_value($userId, 'legal_accept', $toSave);

        // Log event (UTC datetime for DB/log consistency)
        Event::addEvent(
            LOG_TERM_CONDITION_ACCEPTED,
            LOG_USER_OBJECT,
            api_get_user_info($userId),
            DateTimeHelper::localTimeYmdHis($timestamp, 'UTC', 'UTC')
        );

        $bossList = UserManager::getStudentBossList($userId);
        if (!empty($bossList)) {
            $bossIds = array_column($bossList, 'boss_id');
            $current = api_get_user_info($userId);

            // Localized datetime string (platform/user TZ)
            $dateStr = DateTimeHelper::localTime(
                $timestamp,
                null,
                'UTC',
                false,
                true,
                false,
                'Y-m-d H:i:s'
            ) ?? '';

            foreach ($bossIds as $bossId) {
                $subject = \sprintf(get_lang('User %s signed the agreement.'), $current['complete_name']);
                $content = \sprintf(get_lang('User %s signed the agreement the %s.'), $current['complete_name'], $dateStr);
                MessageManager::send_message_simple($bossId, $subject, $content, $userId);
            }
        }
    }

    /**
     * Displays the Terms and Conditions page.
     */
    public static function displayLegalTermsPage(string $returnUrl = '/home', bool $canAccept = true, string $infoMessage = ''): void
    {
        $iso = api_get_language_isocode();
        $langId = api_get_language_id($iso);
        $term = LegalManager::get_last_condition($langId);

        if (!$term) {
            // No T&C for current language → show a message
            Display::display_header(get_lang('Terms and Conditions'));
            echo '<div class="max-w-3xl mx-auto text-gray-90 text-lg text-center">'
                .get_lang('No terms and conditions available for this language.')
                .'</div>';
            Display::display_footer();

            exit;
        }

        Display::display_header(get_lang('Terms and Conditions'));

        if (!empty($term['content'])) {
            echo '<div class="max-w-3xl mx-auto bg-white shadow p-8 rounded">';
            echo '<h1 class="text-2xl font-bold text-primary mb-6">'.get_lang('Terms and Conditions').'</h1>';

            if (!empty($infoMessage)) {
                echo '<div class="mb-4">'.$infoMessage.'</div>';
            }

            echo '<div class="prose prose-sm max-w-none mb-6">'.$term['content'].'</div>';

            $extra = new ExtraFieldValue('terms_and_condition');
            foreach ($extra->getAllValuesByItem($term['id']) as $field) {
                if (!empty($field['field_value'])) {
                    echo '<div class="mb-4">';
                    echo '<h3 class="text-lg font-semibold text-primary">'.$field['display_text'].'</h3>';
                    echo '<p class="text-gray-90 mt-1">'.$field['field_value'].'</p>';
                    echo '</div>';
                }
            }

            echo '<form method="post" action="tc.php?return='.urlencode($returnUrl).'" class="space-y-6">';
            echo '<input type="hidden" name="legal_accept_type" value="'.$term['version'].':'.$term['language_id'].'">';
            echo '<input type="hidden" name="return" value="'.htmlspecialchars($returnUrl).'">';

            if ($canAccept) {
                $hide = 'true' === api_get_setting('registration.hide_legal_accept_checkbox');
                if ($hide) {
                    echo '<input type="hidden" name="legal_accept" value="1">';
                } else {
                    echo '<label class="flex items-start space-x-2">';
                    echo '<input type="checkbox" name="legal_accept" value="1" required class="rounded border-gray-300 text-primary focus:ring-primary">';
                    echo '<span class="text-gray-90 text-sm">'.get_lang('I have read and agree to the').' ';
                    echo '<a href="tc.php?preview=1" target="_blank" class="text-primary hover:underline">'.get_lang('Terms and Conditions').'</a>';
                    echo '</span>';
                    echo '</label>';
                }

                echo '<div><button type="submit" class="inline-block bg-primary text-white font-semibold px-6 py-3 rounded hover:opacity-90 transition">'.get_lang('Accept Terms and Conditions').'</button></div>';
            } else {
                echo '<div><button type="button" class="inline-block bg-gray-400 text-white font-semibold px-6 py-3 rounded cursor-not-allowed" disabled>'.get_lang('Accept Terms and Conditions').'</button></div>';
            }

            echo '</form>';
            echo '</div>';
        } else {
            echo '<div class="text-center text-gray-90 text-lg">'.get_lang('Coming soon...').'</div>';
        }

        Display::display_footer();

        exit;
    }

    /**
     * Try to add a legacy file to a Resource using the repository's addFile() API.
     * Falls back to attachLegacyFileToResource() if addFile() is not available.
     *
     * Returns true on success, false otherwise. Logs in English.
     */
    public static function addLegacyFileToResource(
        string $filePath,
        ResourceRepository $repo,
        AbstractResource $resource,
        string $fileName = '',
        string $description = ''
    ): bool {
        $class = $resource::class;
        $basename = basename($filePath);

        if (!self::legacyFileUsable($filePath)) {
            error_log("LEGACY_FILE: Cannot attach to {$class} – file not found or unreadable: {$basename}");

            return false;
        }

        // If the repository doesn't expose addFile(), use the Asset flow.
        if (!method_exists($repo, 'addFile')) {
            error_log('LEGACY_FILE: Repository '.$repo::class.' has no addFile(), falling back to Asset flow');

            return self::attachLegacyFileToResource($filePath, $resource, $fileName);
        }

        try {
            $mimeType = self::legacyDetectMime($filePath);
            $finalName = '' !== $fileName ? $fileName : $basename;

            // UploadedFile in "test mode" (last arg true) avoids PHP upload checks.
            $uploaded = new UploadedFile($filePath, $finalName, $mimeType, null, true);
            $repo->addFile($resource, $uploaded, $description);

            return true;
        } catch (Throwable $e) {
            error_log('LEGACY_FILE EXCEPTION (addFile): '.$e->getMessage());

            return false;
        }
    }

    /**
     * Create an Asset for a legacy file and attach it to the resource's node.
     * Generic path that works for any AbstractResource with a ResourceNode.
     *
     * Returns true on success, false otherwise. Logs in English.
     */
    public static function attachLegacyFileToResource(
        string $filePath,
        AbstractResource $resource,
        string $fileName = ''
    ): bool {
        $class = $resource::class;
        $basename = basename($filePath);

        if (!self::legacyFileUsable($filePath)) {
            error_log("LEGACY_FILE: Cannot attach Asset to {$class} – file not found or unreadable: {$basename}");

            return false;
        }

        if (!method_exists($resource, 'getResourceNode') || null === $resource->getResourceNode()) {
            error_log("LEGACY_FILE: Resource has no ResourceNode – cannot attach Asset (class: {$class})");

            return false;
        }

        try {
            $assetRepo = Container::getAssetRepository();

            // Prefer a dedicated helper if available.
            if (method_exists($assetRepo, 'createFromLocalPath')) {
                $asset = $assetRepo->createFromLocalPath(
                    $filePath,
                    '' !== $fileName ? $fileName : $basename
                );
            } else {
                // Fallback: simulate an upload-like array for createFromRequest().
                $mimeType = self::legacyDetectMime($filePath);
                $fakeUpload = [
                    'tmp_name' => $filePath,
                    'name' => '' !== $fileName ? $fileName : $basename,
                    'type' => $mimeType,
                    'size' => @filesize($filePath) ?: null,
                    'error' => 0,
                ];

                $asset = (new Asset())
                    ->setTitle($fakeUpload['name'])
                    ->setCompressed(false)
                ;

                // AssetRepository::createFromRequest(Asset $asset, array $uploadLike)
                $assetRepo->createFromRequest($asset, $fakeUpload);
            }

            // Attach to the resource's node.
            if (method_exists($assetRepo, 'attachToNode')) {
                $assetRepo->attachToNode($asset, $resource->getResourceNode());

                return true;
            }

            // If the resource repository exposes a direct helper:
            $repo = self::guessResourceRepository($resource);
            if ($repo && method_exists($repo, 'attachAssetToResource')) {
                $repo->attachAssetToResource($resource, $asset);

                return true;
            }

            error_log('LEGACY_FILE: No method to attach Asset to node (missing attachToNode/attachAssetToResource)');

            return false;
        } catch (Throwable $e) {
            error_log('LEGACY_FILE EXCEPTION (Asset attach): '.$e->getMessage());

            return false;
        }
    }

    private static function legacyFileUsable(string $filePath): bool
    {
        return is_file($filePath) && is_readable($filePath);
    }

    private static function legacyDetectMime(string $filePath): string
    {
        $mime = @mime_content_type($filePath);

        return $mime ?: 'application/octet-stream';
    }

    /**
     * Best-effort guess to find the resource repository via Doctrine.
     * Returns null if the repo is not a ResourceRepository.
     */
    private static function guessResourceRepository(AbstractResource $resource): ?ResourceRepository
    {
        try {
            $em = Database::getManager();
            $repo = $em->getRepository($resource::class);

            return $repo instanceof ResourceRepository ? $repo : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Scan HTML for legacy /courses/<dir>/document/... references found in a ZIP,
     * ensure those files are created as Documents, and return URL maps to rewrite the HTML.
     *
     * Returns: ['byRel' => [ "document/..." => "public-url" ],
     *           'byBase'=> [ "file.ext"     => "public-url" ] ]
     *
     * @param mixed $docRepo
     * @param mixed $courseEntity
     * @param mixed $session
     * @param mixed $group
     */
    public static function buildUrlMapForHtmlFromPackage(
        string $html,
        string $courseDir,
        string $srcRoot,
        array &$folders,
        callable $ensureFolder,
        $docRepo,
        $courseEntity,
        $session,
        $group,
        int $session_id,
        int $file_option,
        ?callable $dbg = null
    ): array {
        $byRel = [];
        $byBase = [];
        $iidByRel = [];
        $iidByBase = [];

        $DBG = $dbg ?: static function ($m, $c = []): void { /* no-op */ };

        // src|href pointing to …/courses/<dir>/document/... (host optional)
        $depRegex = '/(?P<attr>src|href)\s*=\s*["\'](?P<full>(?:(?P<scheme>https?:)?\/\/[^"\']+)?(?P<path>\/(?:app\/)?courses\/[^\/]+\/document\/[^"\']+\.[a-z0-9]{1,8}))["\']/i';

        if (!preg_match_all($depRegex, $html, $mm) || empty($mm['full'])) {
            return ['byRel' => $byRel, 'byBase' => $byBase];
        }

        // Normalize a full URL to a "document/..." relative path inside the package
        $toRel = static function (string $full) use ($courseDir): string {
            $urlPath = parse_url(html_entity_decode($full, ENT_QUOTES | ENT_HTML5), PHP_URL_PATH) ?: $full;
            $urlPath = preg_replace('#^/(?:app/)?courses/([^/]+)/#i', '/courses/'.$courseDir.'/', $urlPath);
            $rel = preg_replace('#^/(?:app/)?courses/'.preg_quote($courseDir, '#').'/#i', '', $urlPath) ?: $urlPath;

            return ltrim($rel, '/'); // "document/..."
        };

        foreach ($mm['full'] as $fullUrl) {
            $rel = $toRel($fullUrl); // e.g. "document/img.png"
            // Do not auto-create HTML files here (they are handled by the main import loop).
            $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
            if (\in_array($ext, ['html', 'htm'], true)) {
                continue;
            }
            if (!str_starts_with($rel, 'document/')) {
                continue;
            }   // STRICT: only /document/*
            if (isset($byRel[$rel])) {
                continue;
            }

            $basename = basename(parse_url($fullUrl, PHP_URL_PATH) ?: $fullUrl);
            $byBase[$basename] = $byBase[$basename] ?? null;

            // Convert "document/..." (package rel) to destination rel "/..."
            $dstRel = '/'.ltrim(substr($rel, \strlen('document/')), '/'); // e.g. "/Videos/img.png"
            $depTitle = basename($dstRel);
            $depAbs = rtrim($srcRoot, '/').'/'.$rel;

            // Destination parent folder (no "/document" prefix in destination)
            $parentRelPath = rtrim(\dirname($dstRel), '/');
            if ('' === $parentRelPath || '.' === $parentRelPath) {
                $parentRelPath = '/';
            }

            $parentId = 0;
            if ('/' !== $parentRelPath) {
                $parentId = $folders[$parentRelPath] ?? 0;
                if (!$parentId) {
                    $parentId = $ensureFolder($parentRelPath);
                    $folders[$parentRelPath] = $parentId;
                    $DBG('helper.ensureFolder', ['parentRelPath' => $parentRelPath, 'parentId' => $parentId]);
                }
            }

            if (!is_file($depAbs) || !is_readable($depAbs)) {
                $DBG('helper.dep.missing', ['rel' => $rel, 'abs' => $depAbs]);

                continue;
            }

            // Collision check under parent
            $parentRes = $parentId ? $docRepo->find($parentId) : $courseEntity;
            $findExisting = function ($t) use ($docRepo, $parentRes, $courseEntity, $session, $group) {
                $e = $docRepo->findCourseResourceByTitle($t, $parentRes->getResourceNode(), $courseEntity, $session, $group);

                return $e && method_exists($e, 'getIid') ? $e->getIid() : null;
            };

            $finalTitle = $depTitle;
            $existsIid = $findExisting($finalTitle);
            if ($existsIid) {
                $FILE_SKIP = \defined('FILE_SKIP') ? FILE_SKIP : 2;
                if ($file_option === $FILE_SKIP) {
                    $existingDoc = $docRepo->find($existsIid);
                    if ($existingDoc) {
                        $url = $docRepo->getResourceFileUrl($existingDoc);
                        if ($url) {
                            $byRel[$rel] = $url;
                            $byBase[$basename] = $byBase[$basename] ?: $url;

                            $iidByRel[$rel] = (int) $existsIid;
                            $iidByBase[$basename] = $iidByBase[$basename] ?? (int) $existsIid;

                            $DBG('helper.dep.reuse', ['rel' => $rel, 'iid' => $existsIid, 'url' => $url]);
                        }
                    }

                    continue;
                }
                // Rename on collision
                $pi = pathinfo($depTitle);
                $name = $pi['filename'] ?? $depTitle;
                $ext2 = isset($pi['extension']) && '' !== $pi['extension'] ? '.'.$pi['extension'] : '';
                $i = 1;
                while ($findExisting($finalTitle)) {
                    $finalTitle = $name.'_'.$i.$ext2;
                    $i++;
                }
            }

            // Create the non-HTML dependency from the package
            try {
                $entity = DocumentManager::addDocument(
                    ['real_id' => $courseEntity->getId(), 'code' => method_exists($courseEntity, 'getCode') ? $courseEntity->getCode() : null],
                    $dstRel, // metadata path (no "/document" root)
                    'file',
                    (int) (@filesize($depAbs) ?: 0),
                    $finalTitle,
                    null,
                    0,
                    null,
                    0,
                    (int) $session_id,
                    0,
                    false,
                    '',
                    $parentId,
                    $depAbs
                );
                $iid = method_exists($entity, 'getIid') ? $entity->getIid() : 0;
                $url = $docRepo->getResourceFileUrl($entity);
                $iidByRel[$rel] = (int) $iid;
                $iidByBase[$basename] = $iidByBase[$basename] ?? (int) $iid;

                $DBG('helper.dep.created', ['rel' => $rel, 'iid' => $iid, 'url' => $url]);

                if ($url) {
                    $byRel[$rel] = $url;
                    $byBase[$basename] = $byBase[$basename] ?: $url;
                }
            } catch (Throwable $e) {
                $DBG('helper.dep.error', ['rel' => $rel, 'err' => $e->getMessage()]);
            }
        }

        $byBase = array_filter($byBase);

        return [
            'byRel' => $byRel,
            'byBase' => $byBase,
            'iidByRel' => $iidByRel,
            'iidByBase' => $iidByBase,
        ];
    }

    /**
     * Rewrite src|href that point to /courses/<dir>/document/... using:
     *  - exact match by relative path ("document/...") via $urlMapByRel
     *  - basename fallback ("file.ext") via $urlMapByBase
     *
     * Returns: ['html'=>..., 'replaced'=>N, 'misses'=>M]
     */
    public static function rewriteLegacyCourseUrlsWithMap(
        string $html,
        string $courseDir,
        array $urlMapByRel,
        array $urlMapByBase
    ): array {
        $replaced = 0;
        $misses = 0;

        $pattern = '/(?P<attr>src|href)\s*=\s*["\'](?P<full>(?:(?P<scheme>https?:)?\/\/[^"\']+)?(?P<path>\/(?:app\/)?courses\/(?P<dir>[^\/]+)\/document\/[^"\']+\.[a-z0-9]{1,8}))["\']/i';

        $html = preg_replace_callback($pattern, function ($m) use ($courseDir, $urlMapByRel, $urlMapByBase, &$replaced, &$misses) {
            $attr = $m['attr'];
            $fullUrl = html_entity_decode($m['full'], ENT_QUOTES | ENT_HTML5);
            $path = $m['path']; // /courses/<dir>/document/...
            $matchDir = $m['dir'];

            // Normalize to current course directory
            $effectivePath = $path;
            if (0 !== strcasecmp($matchDir, $courseDir)) {
                $effectivePath = preg_replace(
                    '#^/(?:app/)?courses/'.preg_quote($matchDir, '#').'/#i',
                    '/courses/'.$courseDir.'/',
                    $path
                ) ?: $path;
            }

            $relInPackage = preg_replace(
                '#^/(?:app/)?courses/'.preg_quote($courseDir, '#').'/#i',
                '',
                $effectivePath
            ) ?: $effectivePath;

            $relInPackage = ltrim($relInPackage, '/'); // document/...

            // 1) exact rel match
            if (isset($urlMapByRel[$relInPackage])) {
                $newUrl = $urlMapByRel[$relInPackage];
                $replaced++;

                return $attr.'="'.htmlspecialchars($newUrl, ENT_QUOTES | ENT_HTML5).'"';
            }

            // 2) basename fallback
            $base = basename(parse_url($effectivePath, PHP_URL_PATH) ?: $effectivePath);
            if (isset($urlMapByBase[$base])) {
                $newUrl = $urlMapByBase[$base];
                $replaced++;

                return $attr.'="'.htmlspecialchars($newUrl, ENT_QUOTES | ENT_HTML5).'"';
            }

            // Not found → keep original
            $misses++;

            return $m[0];
        }, $html);

        return ['html' => $html, 'replaced' => $replaced, 'misses' => $misses];
    }
}
