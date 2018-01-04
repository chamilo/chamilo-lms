<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Form\Type;

use Sylius\Bundle\AttributeBundle\Form\EventSubscriber\BuildAttributeFormSubscriber;
use Sylius\Bundle\ResourceBundle\Form\EventSubscriber\AddCodeFormSubscriber;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Attribute type.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 * @author Mateusz Zalewski <mateusz.zalewski@lakion.com>
 */
class AttributeType extends AbstractResourceType
{
    /**
     * @var string
     */
    protected $subjectName;

    /**
     * @param string $dataClass
     * @param array $validationGroups
     * @param string $subjectName
     */
    public function __construct($dataClass, array $validationGroups, $subjectName)
    {
        parent::__construct($dataClass, $validationGroups);

        $this->subjectName = $subjectName;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventSubscriber(new BuildAttributeFormSubscriber($builder->getFormFactory()))
            ->addEventSubscriber(new AddCodeFormSubscriber())
            /*->add('translations', 'a2lix_translationsForms', array(
                'form_type' => sprintf('sylius_%s_attribute_translation', $this->subjectName),
                'label' => 'sylius.form.attribute.translations',
            ))*/
            ->add('type', 'sylius_attribute_type_choice', array(
                'label'    => 'sylius.form.attribute.type',
                'disabled' => true,
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {       //chamilo_user_extra_field_choice
        return 'chamilo_user_attribute_type';
        return sprintf('%s_extra_field', $this->subjectName);
    }
}
