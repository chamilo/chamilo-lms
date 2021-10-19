<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\TagRepository;
use DateTime;
use GoogleMapsPlugin;
use Oh\GoogleMapFormTypeBundle\Form\Type\GoogleMapType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Security;

class ExtraFieldType extends AbstractType
{
    private ExtraFieldValuesRepository $extraFieldValuesRepository;
    private Security $security;
    private ExtraFieldRepository $extraFieldRepository;
    private TagRepository $tagRepository;

    public function __construct(
        ExtraFieldValuesRepository $extraFieldValuesRepository,
        ExtraFieldRepository $extraFieldRepository,
        TagRepository $tagRepository,
        Security $security
    ) {
        $this->extraFieldValuesRepository = $extraFieldValuesRepository;
        $this->extraFieldRepository = $extraFieldRepository;
        $this->security = $security;
        $this->tagRepository = $tagRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // @todo implement Course/Session extra fields
        /** @var User|null $item */
        $item = $this->security->getUser();

        if (null === $item) {
            return;
        }

        $extraFieldType = ExtraField::USER_FIELD_TYPE; // user/course/session ?
        $extraFields = $this->extraFieldRepository->getExtraFields($extraFieldType);
        $values = $this->extraFieldValuesRepository->getExtraFieldValuesFromItem($item, $extraFieldType);

        $data = [];
        foreach ($values as $value) {
            $data[$value->getField()->getVariable()] = $value->getValue();
        }

        $gMapsPlugin = GoogleMapsPlugin::create();
        $geolocalization = 'true' === $gMapsPlugin->get('enable_api');

        foreach ($extraFields as $extraField) {
            $text = $extraField->getDisplayText();
            $variable = $extraField->getVariable();
            $value = $data[$extraField->getVariable()] ?? null;

            $defaultOptions = [
                'label' => $text,
                'required' => false,
                'by_reference' => false,
                'mapped' => false,
                'data' => $value,
            ];

            // @todo validate data.
            switch ($extraField->getFieldType()) {
                case \ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                case \ExtraField::FIELD_TYPE_DIVIDER:
                case \ExtraField::FIELD_TYPE_TIMEZONE:
                case \ExtraField::FIELD_TYPE_FILE_IMAGE:
                case \ExtraField::FIELD_TYPE_FILE:
                case \ExtraField::FIELD_TYPE_LETTERS_SPACE:
                case \ExtraField::FIELD_TYPE_ALPHANUMERIC_SPACE:
                case \ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD:
                case \ExtraField::FIELD_TYPE_TRIPLE_SELECT:
                    //@todo
                    break;
                case \ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES:
                case \ExtraField::FIELD_TYPE_GEOLOCALIZATION:
                    if (!$geolocalization) {
                        break 2;
                    }

                    $defaultOptions['data'] = [];
                    if (!empty($value)) {
                        $parts = explode('::', $value);
                        $coordinates = explode(',', $parts[1]);
                        $mapArray = [
                            'address' => $parts[0] ?? '',
                            'latitude' => $coordinates[0] ?? '',
                            'longitude' => $coordinates[1] ?? '',
                        ];
                        $defaultOptions['data'] = $mapArray;
                    }

                    $builder->add($variable, GoogleMapType::class, $defaultOptions);

                    break;
                case \ExtraField::FIELD_TYPE_TAG:
                    $defaultOptions['expanded'] = false;
                    $defaultOptions['multiple'] = true;

                    // The class will be loaded in the template: src/CoreBundle/Resources/views/Account/edit.html.twig
                    // @todo implement generic form.
                    $class = 'select2_extra_rel_tag';
                    if (ExtraField::USER_FIELD_TYPE === $extraFieldType) {
                        $class = 'select2_user_rel_tag';
                        $tags = $this->tagRepository->getTagsByUser($extraField, $item);

                        $choices = [];
                        $choicesAttributes = [];
                        foreach ($tags as $tag) {
                            $stringTag = $tag->getTag();
                            if (empty($stringTag)) {
                                continue;
                            }
                            $choices[$stringTag] = $stringTag;
                            $choicesAttributes[$stringTag] = ['data-id' => $tag->getId()];
                        }

                        $defaultOptions['choices'] = $choices;
                        $defaultOptions['choice_attr'] = $choicesAttributes;
                        $defaultOptions['data'] = $choices;
                    }

                    $defaultOptions['attr'] = [
                        'class' => $class,
                        'style' => 'width:500px',
                        'data.field_id' => $extraField->getId(),
                    ];
                    $builder->add($variable, ChoiceType::class, $defaultOptions);

                    break;
                case \ExtraField::FIELD_TYPE_VIDEO_URL:
                case \ExtraField::FIELD_TYPE_SOCIAL_PROFILE:
                    $builder->add($variable, UrlType::class, $defaultOptions);

                    break;
                case \ExtraField::FIELD_TYPE_MOBILE_PHONE_NUMBER:
                    $builder->add($variable, TelType::class, $defaultOptions);

                    break;
                case \ExtraField::FIELD_TYPE_DATE:
                    $defaultOptions['data'] = null;
                    if (!empty($value)) {
                        $defaultOptions['data'] = new DateTime($value);
                    }
                    $defaultOptions['widget'] = 'single_text';
                    $builder->add($variable, DateType::class, $defaultOptions);

                    break;
                case \ExtraField::FIELD_TYPE_DATETIME:
                    $defaultOptions['data'] = null;
                    if (!empty($value)) {
                        $defaultOptions['data'] = new DateTime($value);
                    }
                    $defaultOptions['widget'] = 'single_text';
                    $builder->add($variable, DateTimeType::class, $defaultOptions);

                    break;
                case \ExtraField::FIELD_TYPE_TEXTAREA:
                    $builder->add($variable, TextareaType::class, $defaultOptions);

                    break;
                case \ExtraField::FIELD_TYPE_FLOAT:
                    $builder->add($variable, NumberType::class, $defaultOptions);

                    break;
                case \ExtraField::FIELD_TYPE_INTEGER:
                    $builder->add($variable, IntegerType::class, $defaultOptions);

                    break;
                case \ExtraField::FIELD_TYPE_LETTERS_ONLY:
                case \ExtraField::FIELD_TYPE_ALPHANUMERIC:
                case \ExtraField::FIELD_TYPE_TEXT:
                    $builder->add($variable, TextType::class, $defaultOptions);

                    break;
                case \ExtraField::FIELD_TYPE_CHECKBOX:
                case \ExtraField::FIELD_TYPE_RADIO:
                case \ExtraField::FIELD_TYPE_SELECT:
                case \ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
                    if (empty($value)) {
                        $defaultOptions['data'] = null;
                    }
                    $options = $extraField->getOptions();
                    $choices = [];
                    foreach ($options as $option) {
                        $choices[$option->getDisplayText()] = $option->getValue();
                    }
                    $defaultOptions['choices'] = $choices;

                    if (\ExtraField::FIELD_TYPE_CHECKBOX === $extraField->getFieldType()) {
                        $defaultOptions['expanded'] = true;
                        $defaultOptions['multiple'] = true;
                    }
                    if (\ExtraField::FIELD_TYPE_SELECT === $extraField->getFieldType()) {
                        $defaultOptions['expanded'] = false;
                        $defaultOptions['multiple'] = false;
                    }
                    if (\ExtraField::FIELD_TYPE_SELECT_MULTIPLE === $extraField->getFieldType()) {
                        $defaultOptions['expanded'] = false;
                        $defaultOptions['multiple'] = true;
                    }
                    $builder->add($variable, ChoiceType::class, $defaultOptions);

                    break;
            }
        }

        /*$builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) use ($item, $extraFields): void {
                $data = $event->getData();
                foreach ($extraFields as $extraField) {
                    $newValue = $data[$extraField->getVariable()] ?? null;
                    if (!empty($newValue)) {
                        if (\ExtraField::FIELD_TYPE_TAG === $extraField->getFieldType()) {
                            $formItem = $event->getForm()->get($extraField->getVariable());
                            $formItem->setData($newValue);
                        }
                    }
                }
            }
        );*/
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($item, $extraFields): void {
                $data = $event->getData();
                foreach ($extraFields as $extraField) {
                    $newValue = $data[$extraField->getVariable()] ?? null;

                    switch ($extraField->getFieldType()) {
                        case \ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES:
                        case \ExtraField::FIELD_TYPE_GEOLOCALIZATION:
                            if (!empty($newValue)) {
                                $newValue = $newValue['address'].'::'.$newValue['latitude'].','.$newValue['longitude'];
                            }
                            $this->extraFieldValuesRepository->updateItemData($extraField, $item, $newValue);

                            break;
                        case \ExtraField::FIELD_TYPE_TAG:
                            $formItem = $event->getForm()->get($extraField->getVariable());
                            $options = $formItem->getConfig()->getOptions();
                            $options['choices'] = $newValue;
                            $event->getForm()->add($extraField->getVariable(), ChoiceType::class, $options);

                            foreach ($newValue as $tag) {
                                $this->tagRepository->addTagToUser($extraField, $item, $tag);
                            }

                            break;
                        default:
                            if (empty($newValue)) {
                                break;
                            }
                            $this->extraFieldValuesRepository->updateItemData($extraField, $item, $newValue);

                            break;
                    }
                }
            }
        );
    }
}
