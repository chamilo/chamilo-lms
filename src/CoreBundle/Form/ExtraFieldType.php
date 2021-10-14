<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldItemInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use DateTime;
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

    public function __construct(
        ExtraFieldValuesRepository $extraFieldValuesRepository,
        ExtraFieldRepository $extraFieldRepository,
        Security $security
    ) {
        $this->extraFieldValuesRepository = $extraFieldValuesRepository;
        $this->extraFieldRepository = $extraFieldRepository;
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // @todo implement Course/Session extra fields
        /** @var User|null|ExtraFieldItemInterface $item */
        $item = $this->security->getUser();

        if (null === $item) {
            return;
        }

        $extraFields = $this->extraFieldRepository->getExtraFields(ExtraField::USER_FIELD_TYPE);
        $values = $this->extraFieldValuesRepository->getExtraFieldValuesFromItem($item, ExtraField::USER_FIELD_TYPE);

        $data = [];
        foreach ($values as $value) {
            $data[$value->getField()->getVariable()] = $value->getValue();
        }

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
                case \ExtraField::FIELD_TYPE_TAG:
                case \ExtraField::FIELD_TYPE_TIMEZONE:
                case \ExtraField::FIELD_TYPE_FILE_IMAGE:
                case \ExtraField::FIELD_TYPE_FILE:
                case \ExtraField::FIELD_TYPE_LETTERS_SPACE:
                case \ExtraField::FIELD_TYPE_ALPHANUMERIC_SPACE:
                case \ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES:
                case \ExtraField::FIELD_TYPE_GEOLOCALIZATION:
                case \ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD:
                case \ExtraField::FIELD_TYPE_TRIPLE_SELECT:
                    //@todo
                    break;
                case \ExtraField::FIELD_TYPE_VIDEO_URL:
                case \ExtraField::FIELD_TYPE_SOCIAL_PROFILE:
                    $builder->add($variable, UrlType::class, $defaultOptions);

                    break;
                case \ExtraField::FIELD_TYPE_MOBILE_PHONE_NUMBER:
                    $builder->add($variable, TelType::class, $defaultOptions);

                    break;
                case \ExtraField::FIELD_TYPE_DATE:
                    if (!empty($value)) {
                        $defaultOptions['data'] = new DateTime($value);
                    }
                    $defaultOptions['widget'] = 'single_text';
                    $builder->add($variable, DateType::class, $defaultOptions);

                    break;
                case \ExtraField::FIELD_TYPE_DATETIME:
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

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($item, $extraFields): void {
                $data = $event->getData();
                foreach ($extraFields as $extraField) {
                    $newValue = $data[$extraField->getVariable()] ?? '';
                    if (!empty($newValue)) {
                        if (\ExtraField::FIELD_TYPE_DATE === $extraField->getFieldType()) {
                            var_dump($newValue);
                            exit;
                        }
                        if (\ExtraField::FIELD_TYPE_DATETIME === $extraField->getFieldType()) {
                        }
                    }
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($item, $extraFields): void {
                $data = $event->getData();
                foreach ($extraFields as $extraField) {
                    $newValue = $data[$extraField->getVariable()] ?? null;
                    var_dump($extraField->getVariable(), var_dump($newValue));
                    if (empty($newValue)) {
                        continue;
                    }
                    $this->extraFieldValuesRepository->updateItemData($extraField, $item, $newValue);
                }
            }
        );
    }
}
