<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Form;

use Chamilo\LtiBundle\Entity\ExternalTool;
use SimpleXMLElement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_ENCODING;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLOPT_POST;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYPEER;
use const LIBXML_NONET;

class ExternalToolType extends AbstractType
{
    private const VERSION_1P1 = 'lti1p1';
    private const VERSION_1P3 = 'lti1p3';

    private const KEY_TYPE_JWK = 'jwk_keyset';
    private const KEY_TYPE_RSA = 'rsa_key';

    private const AGS_NONE = 'none';
    private const AGS_SIMPLE = 'simple';
    private const AGS_FULL = 'full';

    private const NRPS_NONE = 'none';
    private const NRPS_CONTEXT_MEMBERSHIP = 'simple';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ExternalTool|null $tool */
        $tool = $builder->getData();
        $parent = $tool ? $tool->getToolParent() : null;
        $isStandaloneTool = null === $parent;

        $advantageServices = $tool && is_array($tool->getAdvantageServices())
            ? $tool->getAdvantageServices()
            : [
                'ags' => self::AGS_NONE,
                'nrps' => self::NRPS_NONE,
            ];

        $currentVersion = $tool && $tool->getVersion()
            ? (string) $tool->getVersion()
            : self::VERSION_1P3;

        $currentPublicKey = ($tool && isset($tool->publicKey)) ? $tool->publicKey : null;

        $currentKeyType = !empty($currentPublicKey)
            ? self::KEY_TYPE_RSA
            : self::KEY_TYPE_JWK;

        $builder
            ->add('title', TextType::class, $this->textFieldOptions('Name'))
            ->add('description', TextareaType::class, $this->textareaFieldOptions('Description', false))
        ;

        if ($isStandaloneTool) {
            $builder
                ->add(
                    'launchUrl',
                    UrlType::class,
                    $this->textFieldOptions(
                        'Launch URL',
                        false,
                        'For LTI 1.3, Launch URL can be completed later after registering Chamilo in the external provider. For LTI 1.0 / 1.1, Launch URL is still required.'
                    )
                )
                ->add(
                    'version',
                    ChoiceType::class,
                    [
                        'label' => 'LTI Version',
                        'mapped' => false,
                        'required' => true,
                        'expanded' => true,
                        'multiple' => false,
                        'data' => $currentVersion,
                        'choices' => [
                            'LTI 1.0 / 1.1' => self::VERSION_1P1,
                            'LTI 1.3.0' => self::VERSION_1P3,
                        ],
                        'row_attr' => [
                            'class' => 'mb-6',
                        ],
                        'label_attr' => [
                            'class' => 'mb-3 block text-body-2 font-semibold text-gray-90',
                        ],
                    ]
                )
                ->add(
                    'consumerKey',
                    TextType::class,
                    $this->textFieldOptions(
                        'Consumer key',
                        false,
                        null,
                        [
                            'data-lti-version-section' => self::VERSION_1P1,
                        ]
                    )
                )
                ->add(
                    'sharedSecret',
                    TextType::class,
                    $this->textFieldOptions(
                        'Shared secret',
                        false,
                        null,
                        [
                            'data-lti-version-section' => self::VERSION_1P1,
                        ]
                    )
                )
                ->add(
                    'publicKeyType',
                    ChoiceType::class,
                    [
                        'label' => 'Public key type',
                        'mapped' => false,
                        'required' => false,
                        'expanded' => true,
                        'multiple' => false,
                        'data' => $currentKeyType,
                        'choices' => [
                            'Keyset URL' => self::KEY_TYPE_JWK,
                            'RSA key' => self::KEY_TYPE_RSA,
                        ],
                        'row_attr' => [
                            'class' => 'mb-6',
                            'data-lti-version-section' => self::VERSION_1P3,
                        ],
                        'label_attr' => [
                            'class' => 'mb-3 block text-body-2 font-semibold text-gray-90',
                        ],
                    ]
                )
                ->add(
                    'jwksUrl',
                    UrlType::class,
                    $this->textFieldOptions(
                        'Jwks URL',
                        false,
                        null,
                        [
                            'data-lti-version-section' => self::VERSION_1P3,
                            'data-lti-key-type-section' => self::KEY_TYPE_JWK,
                        ]
                    )
                )
                ->add(
                    'publicKey',
                    TextareaType::class,
                    [
                        'label' => 'Public key',
                        'mapped' => false,
                        'required' => false,
                        'data' => $currentPublicKey,
                        'row_attr' => [
                            'class' => 'mb-6',
                            'data-lti-version-section' => self::VERSION_1P3,
                            'data-lti-key-type-section' => self::KEY_TYPE_RSA,
                        ],
                        'label_attr' => [
                            'class' => 'mb-2 block text-body-2 font-semibold text-gray-90',
                        ],
                        'attr' => [
                            'class' => implode(' ', [
                                'block min-h-[140px] w-full rounded-xl border border-gray-25 bg-white',
                                'px-4 py-3 font-mono text-body-2 text-gray-90 shadow-sm',
                                'placeholder-gray-50',
                                'focus:border-primary focus:ring-2 focus:ring-primary',
                            ]),
                        ],
                    ]
                )
                ->add(
                    'loginUrl',
                    UrlType::class,
                    $this->textFieldOptions(
                        'Login URL',
                        false,
                        null,
                        [
                            'data-lti-version-section' => self::VERSION_1P3,
                        ]
                    )
                )
                ->add(
                    'redirectUrl',
                    UrlType::class,
                    $this->textFieldOptions(
                        'Redirect URL',
                        false,
                        null,
                        [
                            'data-lti-version-section' => self::VERSION_1P3,
                        ]
                    )
                )
                ->add(
                    'ags',
                    ChoiceType::class,
                    [
                        'label' => 'Assignment and Grades Service',
                        'mapped' => false,
                        'required' => false,
                        'expanded' => true,
                        'multiple' => false,
                        'data' => $advantageServices['ags'] ?? self::AGS_NONE,
                        'choices' => [
                            'Do not use service' => self::AGS_NONE,
                            'Simple service' => self::AGS_SIMPLE,
                            'Full service' => self::AGS_FULL,
                        ],
                        'row_attr' => [
                            'class' => 'mb-6',
                            'data-lti-version-section' => self::VERSION_1P3,
                        ],
                        'label_attr' => [
                            'class' => 'mb-3 block text-body-2 font-semibold text-gray-90',
                        ],
                    ]
                )
                ->add(
                    'nrps',
                    ChoiceType::class,
                    [
                        'label' => 'Names and Role Provisioning Service',
                        'mapped' => false,
                        'required' => false,
                        'expanded' => true,
                        'multiple' => false,
                        'data' => $advantageServices['nrps'] ?? self::NRPS_NONE,
                        'choices' => [
                            'Do not use service' => self::NRPS_NONE,
                            'Use service' => self::NRPS_CONTEXT_MEMBERSHIP,
                        ],
                        'row_attr' => [
                            'class' => 'mb-6',
                            'data-lti-version-section' => self::VERSION_1P3,
                        ],
                        'label_attr' => [
                            'class' => 'mb-3 block text-body-2 font-semibold text-gray-90',
                        ],
                    ]
                )
            ;
        }

        $builder
            ->add(
                'customParams',
                TextareaType::class,
                $this->textareaFieldOptions(
                    'Custom params',
                    false,
                    'Custom params required by the Tool Provider. Format: name=value, one by row.'
                )
            )
            ->add(
                'documentTarget',
                ChoiceType::class,
                [
                    'label' => 'Link target',
                    'mapped' => false,
                    'required' => false,
                    'data' => $tool?->getDocumentTarget() ?: 'iframe',
                    'choices' => [
                        'iframe' => 'iframe',
                        'window' => 'window',
                    ],
                    'row_attr' => [
                        'class' => 'mb-6',
                    ],
                    'label_attr' => [
                        'class' => 'mb-2 block text-body-2 font-semibold text-gray-90',
                    ],
                    'attr' => [
                        'class' => implode(' ', [
                            'block w-full rounded-xl border border-gray-25 bg-white',
                            'px-4 py-3 text-body-2 text-gray-90 shadow-sm',
                            'focus:border-primary focus:ring-2 focus:ring-primary',
                        ]),
                    ],
                ]
            )
        ;

        if ($isStandaloneTool || ($parent && !$parent->isActiveDeepLinking())) {
            $builder->add(
                'activeDeepLinking',
                CheckboxType::class,
                $this->checkboxFieldOptions(
                    'Support Deep-Linking',
                    'Contact your Tool Provider to verify if Deep Linking support is mandatory',
                    false,
                    $tool?->isActiveDeepLinking() ?? false
                )
            );
        }

        $builder
            ->add(
                'shareName',
                CheckboxType::class,
                $this->checkboxFieldOptions(
                    "Share launcher's name",
                    null,
                    false,
                    $tool?->isSharingName() ?? false
                )
            )
            ->add(
                'shareEmail',
                CheckboxType::class,
                $this->checkboxFieldOptions(
                    "Share launcher's email",
                    null,
                    false,
                    $tool?->isSharingEmail() ?? false
                )
            )
            ->add(
                'sharePicture',
                CheckboxType::class,
                $this->checkboxFieldOptions(
                    "Share launcher's picture",
                    null,
                    false,
                    $tool?->isSharingPicture() ?? false
                )
            )
            ->add(
                'save',
                SubmitType::class,
                [
                    'label' => 'Save',
                    'attr' => [
                        'class' => implode(' ', [
                            'inline-flex items-center justify-center',
                            'rounded-xl bg-primary px-5 py-3',
                            'text-body-2 font-semibold text-white',
                            'shadow-xl transition hover:opacity-90',
                            'focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2',
                        ]),
                    ],
                ]
            )
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExternalTool::class,
        ]);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        /** @var ExternalTool|null $tool */
        $tool = $event->getData();
        $form = $event->getForm();

        if (!$tool) {
            return;
        }

        $tool->setPrivacy(
            $form->has('shareName') ? (bool) $form->get('shareName')->getData() : $tool->isSharingName(),
            $form->has('shareEmail') ? (bool) $form->get('shareEmail')->getData() : $tool->isSharingEmail(),
            $form->has('sharePicture') ? (bool) $form->get('sharePicture')->getData() : $tool->isSharingPicture()
        );

        if ($form->has('documentTarget')) {
            $tool->setDocumenTarget((string) $form->get('documentTarget')->getData() ?: 'iframe');
        }

        if ($form->has('activeDeepLinking')) {
            $tool->setActiveDeepLinking((bool) $form->get('activeDeepLinking')->getData());
        }

        if (!$form->has('version')) {
            return;
        }

        $version = (string) $form->get('version')->getData();
        if ('' === $version) {
            $version = self::VERSION_1P3;
        }

        $tool->setVersion($version);

        if (self::VERSION_1P3 === $version) {
            if ('' === trim((string) $tool->getClientId())) {
                $tool->setClientId($this->generateClientId());
            }

            $tool->setLoginUrl(trim((string) $form->get('loginUrl')->getData()) ?: null);
            $tool->setRedirectUrl(trim((string) $form->get('redirectUrl')->getData()) ?: null);

            $publicKeyType = (string) $form->get('publicKeyType')->getData();
            if ('' === $publicKeyType) {
                $publicKeyType = self::KEY_TYPE_JWK;
            }

            if (self::KEY_TYPE_RSA === $publicKeyType) {
                $tool->publicKey = trim((string) $form->get('publicKey')->getData()) ?: null;
                $tool->setJwksUrl(null);
            } else {
                $tool->publicKey = null;
                $tool->setJwksUrl(trim((string) $form->get('jwksUrl')->getData()) ?: null);
            }

            $tool->setAdvantageServices([
                'ags' => (string) $form->get('ags')->getData() ?: self::AGS_NONE,
                'nrps' => (string) $form->get('nrps')->getData() ?: self::NRPS_NONE,
            ]);

            $tool->setConsumerKey(null);
            $tool->setSharedSecret(null);

            return;
        }

        if ('' === trim((string) $tool->getLaunchUrl())) {
            $form->get('launchUrl')->addError(
                new FormError('Launch URL is required for LTI 1.0 / 1.1 tools.')
            );

            return;
        }

        $tool->setLoginUrl(null);
        $tool->setRedirectUrl(null);
        $tool->setJwksUrl(null);
        $tool->publicKey = null;
        $tool->setClientId(null);
        $tool->setAdvantageServices([
            'ags' => self::AGS_NONE,
            'nrps' => self::NRPS_NONE,
        ]);

        if (
            '' === trim((string) $tool->getConsumerKey()) &&
            '' === trim((string) $tool->getSharedSecret())
        ) {
            $cartridgeUrl = $this->getLaunchUrlFromCartridge((string) $tool->getLaunchUrl());

            if (!empty($cartridgeUrl)) {
                $tool->setLaunchUrl($cartridgeUrl);
            }
        }
    }

    private function textFieldOptions(
        string $label,
        bool $required = true,
        ?string $help = null,
        array $rowAttr = []
    ): array {
        return [
            'label' => $label,
            'required' => $required,
            'help' => $help,
            'row_attr' => array_merge(['class' => 'mb-6'], $rowAttr),
            'label_attr' => [
                'class' => 'mb-2 block text-body-2 font-semibold text-gray-90',
            ],
            'help_attr' => [
                'class' => 'mt-2 block text-caption text-gray-50',
            ],
            'attr' => [
                'class' => implode(' ', [
                    'block w-full rounded-xl border border-gray-25 bg-white',
                    'px-4 py-3 text-body-2 text-gray-90 shadow-sm',
                    'placeholder-gray-50',
                    'focus:border-primary focus:ring-2 focus:ring-primary',
                ]),
            ],
        ];
    }

    private function textareaFieldOptions(
        string $label,
        bool $required = true,
        ?string $help = null,
        array $rowAttr = []
    ): array {
        return [
            'label' => $label,
            'required' => $required,
            'help' => $help,
            'row_attr' => array_merge(['class' => 'mb-6'], $rowAttr),
            'label_attr' => [
                'class' => 'mb-2 block text-body-2 font-semibold text-gray-90',
            ],
            'help_attr' => [
                'class' => 'mt-2 block text-caption text-gray-50',
            ],
            'attr' => [
                'class' => implode(' ', [
                    'block min-h-[120px] w-full rounded-xl border border-gray-25 bg-white',
                    'px-4 py-3 text-body-2 text-gray-90 shadow-sm',
                    'placeholder-gray-50',
                    'focus:border-primary focus:ring-2 focus:ring-primary',
                ]),
            ],
        ];
    }

    private function checkboxFieldOptions(
        string $label,
        ?string $help = null,
        bool $mapped = false,
        bool $data = false
    ): array {
        return [
            'label' => $label,
            'mapped' => $mapped,
            'required' => false,
            'data' => $data,
            'help' => $help,
            'row_attr' => [
                'class' => 'mb-4 rounded-xl border border-gray-25 bg-support-2 p-4',
            ],
            'label_attr' => [
                'class' => 'ml-3 text-body-2 font-medium text-gray-90',
            ],
            'help_attr' => [
                'class' => 'mt-2 ml-7 block text-caption text-gray-50',
            ],
            'attr' => [
                'class' => 'h-4 w-4 rounded border-gray-25 text-primary focus:ring-primary',
            ],
        ];
    }

    private function getLaunchUrlFromCartridge(string $launchUrl): ?string
    {
        if ('' === trim($launchUrl)) {
            return null;
        }

        $options = [
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POST => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        $ch = curl_init($launchUrl);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if (0 !== $errno || empty($content)) {
            return null;
        }

        libxml_use_internal_errors(true);
        $sxe = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET);

        if (false === $sxe) {
            return null;
        }

        $result = $sxe->xpath('blti:launch_url');

        if (empty($result)) {
            return null;
        }

        return (string) $result[0];
    }

    private function generateClientId(int $length = 20): string
    {
        $hash = md5((string) mt_rand().time());
        $clientId = '';

        for ($position = 0; $position < $length; $position++) {
            $option = mt_rand(1, 3);

            if (1 === $option) {
                $character = chr(mt_rand(97, 122));
            } elseif (2 === $option) {
                $character = chr(mt_rand(65, 90));
            } else {
                $character = substr($hash, mt_rand(0, strlen($hash) - 1), 1);
            }

            $clientId .= $character;
        }

        return $clientId;
    }
}
