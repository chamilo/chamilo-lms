<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Form;

use Chamilo\LtiBundle\Entity\ExternalTool;
use SimpleXMLElement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
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

class ExternalToolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ExternalTool $tool */
        $tool = $builder->getData();
        $parent = $tool ? $tool->getToolParent() : null;

        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
        ;

        if (null === $parent) {
            $builder
                ->add('launchUrl', UrlType::class)
                ->add('consumerKey', TextType::class, [
                    'required' => false,
                ])
                ->add('sharedSecret', TextType::class, [
                    'required' => false,
                ])
            ;
        }

        $builder->add(
            'customParams',
            TextareaType::class,
            [
                'required' => false,
                'help' => 'Custom params required by the Tool Provider. Format: <code>name=value</code>, one by row.',
            ]
        );

        if (null === $parent
            || ($parent && !$parent->isActiveDeepLinking())
        ) {
            $builder->add(
                'activeDeepLinking',
                CheckboxType::class,
                [
                    'label' => 'Support Deep-Linking',
                    'help' => 'Contact your Tool Provider to verify if Deep Linking support is mandatory',
                    'required' => false,
                ]
            );
        }

        $builder
            ->add(
                'shareName',
                CheckboxType::class,
                [
                    'mapped' => false,
                    'help' => "Share launcher's name",
                    'required' => false,
                ]
            )
            ->add(
                'shareEmail',
                CheckboxType::class,
                [
                    'mapped' => false,
                    'help' => "Share launcher's email",
                    'required' => false,
                ]
            )
            ->add(
                'sharePicture',
                CheckboxType::class,
                [
                    'mapped' => false,
                    'help' => "Share launcher's picture",
                    'required' => false,
                ]
            )
        ;

        $builder->add(
            'save',
            SubmitType::class,
            [
                'attr' => [
                    'class' => 'btn btn--primary',
                ],
            ]
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            [$this, 'onPostSubmit']
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class', ExternalTool::class]);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        /** @var ExternalTool $tool */
        $tool = $event->getData();
        $form = $event->getForm();

        if (!$tool) {
            return;
        }

        $tool->setPrivacy(
            $form->get('shareName')->getData(),
            $form->get('shareEmail')->getData(),
            $form->get('sharePicture')->getData()
        );

        $cartridgeUrl = $this->getLaunchUrlFromCartridge($tool->getLaunchUrl());

        if (!empty($cartridgeUrl)) {
            $tool->setLaunchUrl($cartridgeUrl);
        }
    }

    /**
     * @param string $launchUrl
     *
     * @return string|null
     */
    private function getLaunchUrlFromCartridge($launchUrl)
    {
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

        if (0 !== $errno) {
            return null;
        }

        libxml_use_internal_errors(true);
        $sxe = simplexml_load_string($content);

        if (false === $sxe) {
            return null;
        }

        $xml = new SimpleXMLElement($content);
        $result = $xml->xpath('blti:launch_url');

        if (empty($result)) {
            return null;
        }

        $launchUrl = $result[0];

        return (string) $launchUrl;
    }
}
