<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Sonata\CoreBundle\Form\Type;

use Sonata\CoreBundle\Form\DataTransformer\BooleanTypeToBooleanTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BooleanType extends AbstractType
{
    const TYPE_YES = 1;

    const TYPE_NO = 2;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['transform']) {
            $builder->addModelTransformer(new BooleanTypeToBooleanTransformer());
        }

        // remove in SonataCoreBundle 3.0
        if ($options['catalogue'] !== 'SonataCoreBundle') {
            @trigger_error('Option "catalogue" is deprecated since SonataCoreBundle 2.3.10 and will be removed in 3.0. Use option "translation_domain" instead.', E_USER_DEPRECATED);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @todo Remove it when bumping requirements to SF 2.7+
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices'            => array(
                self::TYPE_YES  => 'label_type_yes',
                self::TYPE_NO   => 'label_type_no',
            ),
            'transform' => false,

            // @deprecated Deprecated as of SonataCoreBundle 2.3.10, to be removed in 3.0.
            'catalogue' => 'SonataCoreBundle',

            // Use directly translation_domain in SonataCoreBundle 3.0
            'translation_domain' => function (Options $options) {
                if ($options['catalogue']) {
                    return $options['catalogue'];
                }

                return $options['translation_domain'];
            },
        ));
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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'sonata_type_boolean';
    }
}
