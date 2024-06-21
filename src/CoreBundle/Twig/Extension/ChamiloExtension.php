<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Twig\Extension;

use Chamilo\CoreBundle\Component\Utils\NameConvention;
use Chamilo\CoreBundle\Entity\ResourceIllustrationInterface;
use Chamilo\CoreBundle\Entity\User;
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
    private NameConvention $nameConvention;

    public function __construct(IllustrationRepository $illustrationRepository, SettingsHelper $helper, RouterInterface $router, NameConvention $nameConvention)
    {
        $this->illustrationRepository = $illustrationRepository;
        $this->helper = $helper;
        $this->router = $router;
        $this->nameConvention = $nameConvention;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('var_dump', 'var_dump'),
            new TwigFilter('icon', 'Display::get_icon_path'),
            new TwigFilter('mdi_icon', 'Display::getMdiIconSimple'),
            new TwigFilter('mdi_icon_t', 'Display::getMdiIconTranslate'),
            new TwigFilter('get_lang', 'get_lang'),
            new TwigFilter('get_plugin_lang', 'get_plugin_lang'),
            new TwigFilter('api_get_local_time', 'api_get_local_time'),
            new TwigFilter('api_convert_and_format_date', 'api_convert_and_format_date'),
            new TwigFilter('format_date', 'api_format_date'),
            new TwigFilter('format_file_size', 'format_file_size'),
            new TwigFilter('date_to_time_ago', 'Display::dateToStringAgoAndLongDate'),
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
        ];
    }

    public function completeUserNameWithLink(User $user): string
    {
        $url = $this->router->generate(
            'legacy_main',
            [
                'name' => '/inc/ajax/user_manager.ajax.php?a=get_user_popup&user_id='.$user->getId(),
            ]
        );

        $name = $this->nameConvention->getPersonName($user);

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
        return $this->helper->getSettingsParameter($name);
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
                'helpText' => sprintf(
                    get_lang('Minimum %s characters in total'),
                    $minRequirements['length']
                ),
            ];
        }

        if ($minRequirements['lowercase'] > 0) {
            $options['rules'][] = [
                'minChar' => $minRequirements['lowercase'],
                'pattern' => '[a-z]',
                'helpText' => sprintf(
                    get_lang('Minimum %s lowercase characters'),
                    $minRequirements['lowercase']
                ),
            ];
        }

        if ($minRequirements['uppercase'] > 0) {
            $options['rules'][] = [
                'minChar' => $minRequirements['uppercase'],
                'pattern' => '[A-Z]',
                'helpText' => sprintf(
                    get_lang('Minimum %s uppercase characters'),
                    $minRequirements['uppercase']
                ),
            ];
        }

        if ($minRequirements['numeric'] > 0) {
            $options['rules'][] = [
                'minChar' => $minRequirements['numeric'],
                'pattern' => '[0-9]',
                'helpText' => sprintf(
                    get_lang('Minimum %s numerical (0-9) characters'),
                    $minRequirements['numeric']
                ),
            ];
        }

        if ($minRequirements['specials'] > 0) {
            $options['rules'][] = [
                'minChar' => $minRequirements['specials'],
                'pattern' => '[!"#$%&\'()*+,\-./\\\:;<=>?@[\\]^_`{|}~]',
                'helpText' => sprintf(
                    get_lang('Minimum %s special characters'),
                    $minRequirements['specials']
                ),
            ];
        }

        return "<script>
        (function($) {
            $.fn.passwordCheckerChange = function(options) {
                var settings = $.extend({
                    rules: []
                }, options );

                return this.each(function() {
                    var \$passwordInput = $(this);
                    var \$requirements = $('#password-requirements');

                    function validatePassword(password) {
                        var html = '';

                        settings.rules.forEach(function(rule) {
                            var isValid = new RegExp(rule.pattern).test(password) && password.length >= rule.minChar;
                            var color = isValid ? 'green' : 'red';
                            html += '<li style=\"color:' + color + '\">' + rule.helpText + '</li>';
                        });

                        \$requirements.html(html);
                    }

                    \$passwordInput.on('input', function() {
                        validatePassword(\$passwordInput.val());
                        \$requirements.show();
                    });

                    \$passwordInput.on('blur', function() {
                        \$requirements.hide();
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
}
