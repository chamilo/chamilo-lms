<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests;

use Sonata\CoreBundle\DependencyInjection\Compiler\AdapterCompilerPass;
use Sonata\CoreBundle\DependencyInjection\Compiler\FormFactoryCompilerPass;
use Sonata\CoreBundle\DependencyInjection\Compiler\StatusRendererCompilerPass;
use Sonata\CoreBundle\Form\FormHelper;
use Sonata\CoreBundle\SonataCoreBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * @author Ahmet Akbana <ahmetakbana@gmail.com>
 */
final class SonataCoreBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $containerBuilder = $this->getMock(
            'Symfony\Component\DependencyInjection\ContainerBuilder',
            array('addCompilerPass')
        );

        $containerBuilder->expects($this->exactly(3))
            ->method('addCompilerPass')
            ->will($this->returnCallback(function (CompilerPassInterface $pass) {
                if ($pass instanceof StatusRendererCompilerPass) {
                    return;
                }

                if ($pass instanceof AdapterCompilerPass) {
                    return;
                }

                if ($pass instanceof FormFactoryCompilerPass) {
                    return;
                }

                $this->fail(sprintf(
                    'Compiler pass is not one of the expected types. 
                    Expects "Sonata\AdminBundle\DependencyInjection\Compiler\StatusRendererCompilerPass", 
                    "Sonata\AdminBundle\DependencyInjection\Compiler\AdapterCompilerPass" or 
                    "Sonata\AdminBundle\DependencyInjection\Compiler\FormFactoryCompilerPass", but got "%s".',
                    get_class($pass)
                ));
            }));

        $bundle = new SonataCoreBundle();
        $bundle->build($containerBuilder);

        $this->assertMappingTypeRegistered('form', 'Symfony\Component\Form\Extension\Core\Type\FormType');
    }

    public function testBootCallsRegisterFormMapping()
    {
        $bundle = new SonataCoreBundle();
        $bundle->boot();

        $this->assertMappingTypeRegistered('form', 'Symfony\Component\Form\Extension\Core\Type\FormType');
    }

    /**
     * @dataProvider getRegisteredFormMappingAndTypes
     *
     * @param string $mapping
     * @param string $type
     */
    public function testRegisterFormTypeMapping($mapping, $type)
    {
        $bundle = new SonataCoreBundle();
        $bundle->registerFormMapping();

        $this->assertMappingTypeRegistered($mapping, $type);
    }

    public function getRegisteredFormMappingAndTypes()
    {
        return array(
            array('form', 'Symfony\Component\Form\Extension\Core\Type\FormType'),
            array('birthday', 'Symfony\Component\Form\Extension\Core\Type\BirthdayType'),
            array('checkbox', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType'),
            array('choice', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType'),
            array('collection', 'Symfony\Component\Form\Extension\Core\Type\CollectionType'),
            array('country', 'Symfony\Component\Form\Extension\Core\Type\CountryType'),
            array('date', 'Symfony\Component\Form\Extension\Core\Type\DateType'),
            array('datetime', 'Symfony\Component\Form\Extension\Core\Type\DateTimeType'),
            array('email', 'Symfony\Component\Form\Extension\Core\Type\EmailType'),
            array('file', 'Symfony\Component\Form\Extension\Core\Type\FileType'),
            array('hidden', 'Symfony\Component\Form\Extension\Core\Type\HiddenType'),
            array('integer', 'Symfony\Component\Form\Extension\Core\Type\IntegerType'),
            array('language', 'Symfony\Component\Form\Extension\Core\Type\LanguageType'),
            array('locale', 'Symfony\Component\Form\Extension\Core\Type\LocaleType'),
            array('money', 'Symfony\Component\Form\Extension\Core\Type\MoneyType'),
            array('number', 'Symfony\Component\Form\Extension\Core\Type\NumberType'),
            array('password', 'Symfony\Component\Form\Extension\Core\Type\PasswordType'),
            array('percent', 'Symfony\Component\Form\Extension\Core\Type\PercentType'),
            array('radio', 'Symfony\Component\Form\Extension\Core\Type\RadioType'),
            array('repeated', 'Symfony\Component\Form\Extension\Core\Type\RepeatedType'),
            array('search', 'Symfony\Component\Form\Extension\Core\Type\SearchType'),
            array('textarea', 'Symfony\Component\Form\Extension\Core\Type\TextareaType'),
            array('text', 'Symfony\Component\Form\Extension\Core\Type\TextType'),
            array('time', 'Symfony\Component\Form\Extension\Core\Type\TimeType'),
            array('timezone', 'Symfony\Component\Form\Extension\Core\Type\TimezoneType'),
            array('url', 'Symfony\Component\Form\Extension\Core\Type\UrlType'),
            array('button', 'Symfony\Component\Form\Extension\Core\Type\ButtonType'),
            array('submit', 'Symfony\Component\Form\Extension\Core\Type\SubmitType'),
            array('reset', 'Symfony\Component\Form\Extension\Core\Type\ResetType'),
            array('currency', 'Symfony\Component\Form\Extension\Core\Type\CurrencyType'),
            array('entity', 'Symfony\Bridge\Doctrine\Form\Type\EntityType'),
            array('sonata_type_immutable_array', 'Sonata\CoreBundle\Form\Type\ImmutableArrayType'),
            array('sonata_type_boolean', 'Sonata\CoreBundle\Form\Type\BooleanType'),
            array('sonata_type_collection', 'Sonata\CoreBundle\Form\Type\CollectionType'),
            array('sonata_type_translatable_choice', 'Sonata\CoreBundle\Form\Type\TranslatableChoiceType'),
            array('sonata_type_date_range', 'Sonata\CoreBundle\Form\Type\DateRangeType'),
            array('sonata_type_datetime_range', 'Sonata\CoreBundle\Form\Type\DateTimeRangeType'),
            array('sonata_type_date_picker', 'Sonata\CoreBundle\Form\Type\DatePickerType'),
            array('sonata_type_datetime_picker', 'Sonata\CoreBundle\Form\Type\DateTimePickerType'),
            array('sonata_type_date_range_picker', 'Sonata\CoreBundle\Form\Type\DateRangePickerType'),
            array('sonata_type_datetime_range_picker', 'Sonata\CoreBundle\Form\Type\DateTimeRangePickerType'),
            array('sonata_type_equal', 'Sonata\CoreBundle\Form\Type\EqualType'),
            array('sonata_type_color_selector', 'Sonata\CoreBundle\Form\Type\ColorSelectorType'),
        );
    }

    /**
     * @dataProvider getRegisteredFormMappingAndExtensions
     *
     * @param string $mapping
     * @param string $extension
     */
    public function testRegisterFormExtensionMapping($mapping, $extension)
    {
        $bundle = new SonataCoreBundle();
        $bundle->registerFormMapping();

        $this->assertMappingExtensionRegistered($mapping, $extension);
    }

    public function getRegisteredFormMappingAndExtensions()
    {
        return array(
            array('form', 'form.type_extension.form.http_foundation'),
            array('form', 'form.type_extension.form.validator'),
            array('form', 'form.type_extension.csrf'),
            array('form', 'form.type_extension.form.data_collector'),
            array('form', 'nelmio_api_doc.form.extension.description_form_type_extension'),
            array('repeated', 'form.type_extension.repeated.validator'),
            array('submit', 'form.type_extension.submit.validator'),
        );
    }

    public function testRegisterFormMappingWithContainer()
    {
        $bundle = new SonataCoreBundle();

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $container->expects($this->once())
            ->method('hasParameter')
            ->willReturn(true);

        $container->expects($this->at(1))
            ->method('getParameter')
            ->with('sonata.core.form.mapping.type')
            ->willReturn(array('fooMapping' => 'barType'));

        $container->expects($this->at(2))
            ->method('getParameter')
            ->with('sonata.core.form.mapping.extension')
            ->willReturn(array('fooMapping' => array('barExtension')));

        $reflectedBundle = new \ReflectionClass('Sonata\CoreBundle\SonataCoreBundle');
        $reflectedContainer = $reflectedBundle->getProperty('container');
        $reflectedContainer->setAccessible(true);
        $reflectedContainer->setValue($bundle, $container);

        $bundle->registerFormMapping();

        $this->assertMappingTypeRegistered('fooMapping', 'barType');
        $this->assertMappingExtensionRegistered('fooMapping', 'barExtension');
    }

    /**
     * Asserts mapping type registered.
     *
     * @param string $mapping
     * @param string $type
     */
    private function assertMappingTypeRegistered($mapping, $type)
    {
        $registeredMapping = FormHelper::getFormTypeMapping();

        $this->assertArrayHasKey($mapping, $registeredMapping);
        $this->assertSame($type, $registeredMapping[$mapping]);
    }

    /**
     * Asserts mapping extension registered.
     *
     * @param string $mapping
     * @param string $extension
     */
    private function assertMappingExtensionRegistered($mapping, $extension)
    {
        $registeredMapping = FormHelper::getFormExtensionMapping();

        $this->assertArrayHasKey($mapping, $registeredMapping);
        $this->assertContains($extension, $registeredMapping[$mapping]);
    }
}
