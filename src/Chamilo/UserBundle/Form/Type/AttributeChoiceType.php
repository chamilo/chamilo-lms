<?php

namespace Chamilo\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Attribute choice form type.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
abstract class AttributeChoiceType extends AbstractType
{
    /**
     * Name of the attributes subject.
     *
     * @var string
     */
    protected $subjectName;

    /**
     * Attribute class name.
     *
     * @var string
     */
    protected $className;

    /**
     * Constructor.
     *
     * @param string $subjectName
     * @param string $className
     */
    public function __construct($subjectName, $className)
    {
        $this->subjectName = $subjectName;
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(array(
                'class' => $this->className
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return sprintf('chamilo_%s_extra_field_choice', $this->subjectName);
    }
}
