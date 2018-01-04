<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Attribute choice form type.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class AttributeTypeChoiceType extends AbstractType
{
    /**
     * @var array
     */
    private $attributeTypes;

    /**
     * @param array $attributeTypes
     */
    public function __construct($attributeTypes)
    {
        $this->attributeTypes = $attributeTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(
                array('choices' => $this->attributeTypes)
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        //return sprintf('chamilo_%s_extra_field_choice', $this->subjectName);
        return 'chamilo_user_attribute_choice';
    }
}
