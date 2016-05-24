<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle;

use Sonata\CoreBundle\DependencyInjection\Compiler\AdapterCompilerPass;
use Sonata\CoreBundle\DependencyInjection\Compiler\FormFactoryCompilerPass;
use Sonata\CoreBundle\DependencyInjection\Compiler\StatusRendererCompilerPass;
use Sonata\CoreBundle\Form\FormHelper;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SonataCoreBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new StatusRendererCompilerPass());
        $container->addCompilerPass(new AdapterCompilerPass());
        $container->addCompilerPass(new FormFactoryCompilerPass());

        $this->registerFormMapping();
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        // not sur we need this at Runtime ...
        $this->registerFormMapping();
    }

    /**
     * Register form mapping information.
     */
    public function registerFormMapping()
    {
        // symfony
        FormHelper::registerFormTypeMapping(array(
            'form'       => 'Symfony\Component\Form\Extension\Core\Type\FormType',
            'birthday'   => 'Symfony\Component\Form\Extension\Core\Type\BirthdayType',
            'checkbox'   => 'Symfony\Component\Form\Extension\Core\Type\CheckboxType',
            'choice'     => 'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
            'collection' => 'Symfony\Component\Form\Extension\Core\Type\CollectionType',
            'country'    => 'Symfony\Component\Form\Extension\Core\Type\CountryType',
            'date'       => 'Symfony\Component\Form\Extension\Core\Type\DateType',
            'datetime'   => 'Symfony\Component\Form\Extension\Core\Type\DateTimeType',
            'email'      => 'Symfony\Component\Form\Extension\Core\Type\EmailType',
            'file'       => 'Symfony\Component\Form\Extension\Core\Type\FileType',
            'hidden'     => 'Symfony\Component\Form\Extension\Core\Type\HiddenType',
            'integer'    => 'Symfony\Component\Form\Extension\Core\Type\IntegerType',
            'language'   => 'Symfony\Component\Form\Extension\Core\Type\LanguageType',
            'locale'     => 'Symfony\Component\Form\Extension\Core\Type\LocaleType',
            'money'      => 'Symfony\Component\Form\Extension\Core\Type\MoneyType',
            'number'     => 'Symfony\Component\Form\Extension\Core\Type\NumberType',
            'password'   => 'Symfony\Component\Form\Extension\Core\Type\PasswordType',
            'percent'    => 'Symfony\Component\Form\Extension\Core\Type\PercentType',
            'radio'      => 'Symfony\Component\Form\Extension\Core\Type\RadioType',
            'repeated'   => 'Symfony\Component\Form\Extension\Core\Type\RepeatedType',
            'search'     => 'Symfony\Component\Form\Extension\Core\Type\SearchType',
            'textarea'   => 'Symfony\Component\Form\Extension\Core\Type\TextareaType',
            'text'       => 'Symfony\Component\Form\Extension\Core\Type\TextType',
            'time'       => 'Symfony\Component\Form\Extension\Core\Type\TimeType',
            'timezone'   => 'Symfony\Component\Form\Extension\Core\Type\TimezoneType',
            'url'        => 'Symfony\Component\Form\Extension\Core\Type\UrlType',
            'button'     => 'Symfony\Component\Form\Extension\Core\Type\ButtonType',
            'submit'     => 'Symfony\Component\Form\Extension\Core\Type\SubmitType',
            'reset'      => 'Symfony\Component\Form\Extension\Core\Type\ResetType',
            'currency'   => 'Symfony\Component\Form\Extension\Core\Type\CurrencyType',
            'entity'     => 'Symfony\Bridge\Doctrine\Form\Type\EntityType',
        ));

        // core bundle
        FormHelper::registerFormTypeMapping(array(
            'sonata_type_immutable_array'       => 'Sonata\CoreBundle\Form\Type\ImmutableArrayType',
            'sonata_type_boolean'               => 'Sonata\CoreBundle\Form\Type\BooleanType',
            'sonata_type_collection'            => 'Sonata\CoreBundle\Form\Type\CollectionType',
            'sonata_type_translatable_choice'   => 'Sonata\CoreBundle\Form\Type\TranslatableChoiceType',
            'sonata_type_date_range'            => 'Sonata\CoreBundle\Form\Type\DateRangeType',
            'sonata_type_datetime_range'        => 'Sonata\CoreBundle\Form\Type\DateTimeRangeType',
            'sonata_type_date_picker'           => 'Sonata\CoreBundle\Form\Type\DatePickerType',
            'sonata_type_datetime_picker'       => 'Sonata\CoreBundle\Form\Type\DateTimePickerType',
            'sonata_type_date_range_picker'     => 'Sonata\CoreBundle\Form\Type\DateRangePickerType',
            'sonata_type_datetime_range_picker' => 'Sonata\CoreBundle\Form\Type\DateTimeRangePickerType',
            'sonata_type_equal'                 => 'Sonata\CoreBundle\Form\Type\EqualType',
            'sonata_type_color_selector'        => 'Sonata\CoreBundle\Form\Type\ColorSelectorType',
        ));

        $formTypes = array(
            'form.type_extension.form.http_foundation',
            'form.type_extension.form.validator',
            'form.type_extension.csrf',
            'form.type_extension.form.data_collector',
        );

        if (class_exists('Nelmio\ApiDocBundle\Form\Extension\DescriptionFormTypeExtension')) {
            $formTypes[] = 'nelmio_api_doc.form.extension.description_form_type_extension';
        }

        FormHelper::registerFormExtensionMapping('form', $formTypes);

        FormHelper::registerFormExtensionMapping('repeated', array(
            'form.type_extension.repeated.validator',
        ));

        FormHelper::registerFormExtensionMapping('submit', array(
            'form.type_extension.submit.validator',
        ));

        if ($this->container && $this->container->hasParameter('sonata.core.form.mapping.type')) {
            // from configuration file
            FormHelper::registerFormTypeMapping($this->container->getParameter('sonata.core.form.mapping.type'));
            foreach ($this->container->getParameter('sonata.core.form.mapping.extension') as $ext => $types) {
                FormHelper::registerFormExtensionMapping($ext, $types);
            }
        }
    }
}
