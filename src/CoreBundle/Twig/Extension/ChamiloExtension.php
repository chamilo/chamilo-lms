<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Twig\Extension;

use Chamilo\CoreBundle\Entity\ResourceIllustrationInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\NameConventionHelper;
use Chamilo\CoreBundle\Helpers\ThemeHelper;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Twig\SettingsHelper;
use Security;
use Sylius\Bundle\SettingsBundle\Model\SettingsInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Extend Twig with specifics from Chamilo. For globals, look into TwigListener.php.
 */
class ChamiloExtension extends AbstractExtension
{
    private IllustrationRepository $illustrationRepository;
    private SettingsHelper $helper;
    private RouterInterface $router;
    private NameConventionHelper $nameConventionHelper;

    public function __construct(
        IllustrationRepository $illustrationRepository,
        SettingsHelper $helper,
        RouterInterface $router,
        NameConventionHelper $nameConventionHelper,
        private readonly ThemeHelper $themeHelper,
        private readonly IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {
        $this->illustrationRepository = $illustrationRepository;
        $this->helper = $helper;
        $this->router = $router;
        $this->nameConventionHelper = $nameConventionHelper;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('var_dump', 'var_dump'),
            new TwigFilter('icon', 'Display::get_icon_path'),
            new TwigFilter('mdi_icon', 'Display::getMdiIconSimple', ['is_safe' => ['html']]),
            new TwigFilter('mdi_icon_t', 'Display::getMdiIconTranslate', ['is_safe' => ['html']]),
            new TwigFilter('get_lang', 'get_lang'),
            new TwigFilter('get_plugin_lang', 'get_plugin_lang'),
            new TwigFilter('api_get_local_time', 'api_get_local_time'),
            new TwigFilter('api_convert_and_format_date', 'api_convert_and_format_date'),
            new TwigFilter('format_date', 'api_format_date'),
            new TwigFilter('format_file_size', 'format_file_size'),
            new TwigFilter('date_to_time_ago', 'Display::dateToStringAgoAndLongDate', ['is_safe' => ['html']]),
            new TwigFilter('api_get_configuration_value', 'api_get_configuration_value'),
            new TwigFilter('remove_xss', 'Security::remove_XSS'),
            new TwigFilter('user_complete_name', 'UserManager::formatUserFullName'),
            new TwigFilter('user_complete_name_with_link', $this->completeUserNameWithLink(...)),
            new TwigFilter('illustration', $this->getIllustration(...)),
            new TwigFilter('api_get_setting', $this->getSettingsParameter(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('chamilo_settings_all', $this->getSettings(...)),
            new TwigFunction('chamilo_settings_get', $this->getSettingsParameter(...)),
            new TwigFunction('chamilo_settings_has', [$this, 'hasSettingsParameter']),
            new TwigFunction('password_checker_js', [$this, 'getPasswordCheckerJs'], ['is_safe' => ['html']]),
            new TwigFunction('theme_asset', $this->getThemeAssetUrl(...)),
            new TwigFunction('theme_asset_link_tag', $this->getThemeAssetLinkTag(...), ['is_safe' => ['html']]),
            new TwigFunction(
                'theme_asset_script_tag',
                $this->themeHelper->getThemeAssetScriptTag(...),
                ['is_safe' => ['html']]
            ),
            new TwigFunction('theme_asset_base64', $this->getThemeAssetBase64Encoded(...)),
            new TwigFunction('theme_logo', $this->getThemeLogoUrl(...)),
            new TwigFunction('is_allowed_to_edit', $this->isAllowedToEditHelper->check(...)),
        ];
    }

    public function getThemeLogoUrl(string $type = 'header', bool $absoluteUrl = false): string
    {
        return $this->themeHelper->getPreferredLogoUrl($type, $absoluteUrl);
    }

    public function completeUserNameWithLink(User $user): string
    {
        $url = '/main/inc/ajax/user_manager.ajax.php?a=get_user_popup&user_id='.$user->getId();

        $name = $this->nameConventionHelper->getPersonName($user);

        return "<a href=\"$url\" class=\"ajax\">$name</a>";
    }

    public function getIllustration(ResourceIllustrationInterface $resource): string
    {
        return $this->illustrationRepository->getIllustrationUrl($resource);
    }

    public function getSettings($namespace): SettingsInterface
    {
        return $this->helper->getSettings($namespace);
    }

    public function getSettingsParameter($name)
    {
        $value = $this->helper->getSettingsParameter($name);
        // We only want to inject valid HTML snippets here.
        if ('tracking.header_extra_content' === $name || 'tracking.footer_extra_content' === $name) {
            return $this->resolveTrackingExtraContentValue($name, $value);
        }

        return $value;
    }

    /**
     * Generates and returns JavaScript code for a password strength checker.
     */
    public function getPasswordCheckerJs(string $passwordInputId): ?string
    {
        $checkPass = api_get_setting('allow_strength_pass_checker');
        $useStrengthPassChecker = 'true' === $checkPass;

        if (false === $useStrengthPassChecker) {
            return null;
        }

        $minRequirements = Security::getPasswordRequirements()['min'];

        $options = [
            'rules' => [],
        ];

        if ($minRequirements['length'] > 0) {
            $options['rules'][] = [
                'minChar' => $minRequirements['length'],
                'pattern' => '.',
                'helpText' => \sprintf(
                    get_lang('Minimum %s characters in total'),
                    $minRequirements['length']
                ),
            ];
        }

        if ($minRequirements['lowercase'] > 0) {
            $options['rules'][] = [
                'minChar' => $minRequirements['lowercase'],
                'pattern' => '[a-z]',
                'helpText' => \sprintf(
                    get_lang('Minimum %s lowercase characters'),
                    $minRequirements['lowercase']
                ),
            ];
        }

        if ($minRequirements['uppercase'] > 0) {
            $options['rules'][] = [
                'minChar' => $minRequirements['uppercase'],
                'pattern' => '[A-Z]',
                'helpText' => \sprintf(
                    get_lang('Minimum %s uppercase characters'),
                    $minRequirements['uppercase']
                ),
            ];
        }

        if ($minRequirements['numeric'] > 0) {
            $options['rules'][] = [
                'minChar' => $minRequirements['numeric'],
                'pattern' => '[0-9]',
                'helpText' => \sprintf(
                    get_lang('Minimum %s numerical (0-9) characters'),
                    $minRequirements['numeric']
                ),
            ];
        }

        if ($minRequirements['specials'] > 0) {
            $options['rules'][] = [
                'minChar' => $minRequirements['specials'],
                'pattern' => '[!"#$%&\'()*+,\-./\\\:;<=>?@[\]^_`{|}~]',
                'helpText' => \sprintf(
                    get_lang('Minimum %s special characters'),
                    $minRequirements['specials']
                ),
            ];
        }

        // JS: count occurrences, show only unmet rules, hide initially and when all pass
        return "<script>
            (function($) {
              $.fn.passwordCheckerChange = function(options) {
                var settings = $.extend({ rules: [] }, options);
                var GREEN = '#16a34a';
                var RED   = '#dc2626';

                function escapeHtml(s) {
                  return $('<div>').text(s).html();
                }
                function count(pattern, value) {
                  try {
                    var re = new RegExp(pattern, 'g');
                    var m = (value || '').match(re);
                    return m ? m.length : 0;
                  } catch (e) {
                    return 0;
                  }
                }

                return this.each(function() {
                  var \$passwordInput = $(this);
                  var \$requirements  = $('#password-requirements');
                  if (!\$requirements.length) return;

                  function evaluate(password) {
                    return settings.rules.map(function(rule) {
                      var ok;
                      if (rule.pattern === '.') {
                        ok = (password || '').length >= (rule.minChar || 0);
                      } else {
                        ok = count(rule.pattern, password) >= (rule.minChar || 1);
                      }
                      return { ok: ok, text: rule.helpText };
                    });
                  }

                  function render(password) {
                    if (!password) { \$requirements.hide().empty(); return; }

                    var results = evaluate(password);

                    var allOk = results.every(function(r){ return r.ok; });
                    if (allOk) { \$requirements.hide().empty(); return; }

                    var html = results.map(function(r) {
                      var color = r.ok ? GREEN : RED;
                      var icon  = r.ok ? '✓' : '✗';
                      return '<li class=\"mt-1\" style=\"color:'+color+'\">'+icon+' '+escapeHtml(r.text)+'</li>';
                    }).join('');
                    \$requirements.html(html).show();
                  }

                  \$requirements.hide().empty();

                  \$passwordInput.on('input', function() { render(\$passwordInput.val()); });
                  \$passwordInput.on('blur',  function() {
                    if (!\$passwordInput.val()) { \$requirements.hide().empty(); }
                  });
                });
              };
            }(jQuery));

            $(function() {
              $('".$passwordInputId."').passwordCheckerChange(".json_encode($options).');
            });
            </script>';
    }

    /**
     * Returns the name of the extension.
     */
    public function getName(): string
    {
        return 'chamilo_extension';
    }

    public function getThemeAssetUrl(string $path, bool $absoluteUrl = false): string
    {
        return $this->themeHelper->getThemeAssetUrl($path, $absoluteUrl);
    }

    public function getThemeAssetLinkTag(string $path, bool $absoluteUrl = false): string
    {
        return $this->themeHelper->getThemeAssetLinkTag($path, $absoluteUrl);
    }

    public function getThemeAssetBase64Encoded(string $path): string
    {
        return $this->themeHelper->getAssetBase64Encoded($path);
    }

    /**
     * Normalize legacy tracking extra content values.
     *
     * Expected value: an HTML snippet (e.g. <script>...</script>, <meta ...>, etc.)
     *
     * Problem on migrated portals:
     * - Legacy migration can store a filesystem path (e.g. "app/home/header_extra_content.txt")
     * - Rendering that value produces a visible "flash" of the path before navigation completes.
     *
     * Rule:
     * - Only allow HTML snippets (must contain "<").
     * - If the value looks like a file path / file reference, return empty string.
     * - If the value is plain text (no "<"), return empty string (do not inject as |raw).
     */
    private function resolveTrackingExtraContentValue(string $settingName, mixed $value): string
    {
        if (!\in_array($settingName, ['tracking.header_extra_content', 'tracking.footer_extra_content'], true)) {
            return \is_string($value) ? $value : '';
        }

        if (!\is_string($value)) {
            return '';
        }

        $value = trim($value);
        if ('' === $value) {
            return '';
        }

        // Only allow HTML snippets. Anything else should not be injected with |raw.
        if (!str_contains($value, '<')) {
            return '';
        }

        // Reject obvious file paths or filename references to avoid injecting legacy migrated values.
        // Examples: "app/home/header_extra_content.txt", "/var/www/...", "C:\path\file.txt"
        $looksLikePath = str_contains($value, '/') || str_contains($value, '\\');
        $looksLikeFile = (bool) preg_match('/\.[a-z0-9]{1,8}\b/i', $value);

        if ($looksLikePath && $looksLikeFile) {
            return '';
        }

        return $value;
    }
}
