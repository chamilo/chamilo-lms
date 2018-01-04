<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Form;

use Chamilo\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class UserType
 * @deprecated
 * @package Chamilo\UserBundle\Form
 */
class UserType extends AbstractType
{
    private $securityContext;

    public function __construct(SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * @inheritdoc
     **/
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentUser = $this->securityContext->getToken()->getUser();

        $builder
            ->add('firstname', 'text')
            ->add('lastname', 'text')
            ->add('official_code', 'text')
            ->add('email', 'email')
            ->add('username', 'text')
            ->add('phone', 'text')
            ->add('password', 'password')
            ->add('groups')
            ->add('timezone', 'timezone')
            ->add(
                'locale',
                'locale',
                array('preferred_choices' => array('en', 'fr', 'es'))
            )
            ->add(
                'picture_uri',
                'sonata_media_type',
                array(
                    'provider' => 'sonata.media.provider.image',
                    'context' => 'user_image',
                    'required' => false,
                )
            )
            /*->add(
                'extraFields',
                'collection',
                array(
                    'required' => false,
                    //'type' => 'chamilo_user.form.type.attribute_value_type',
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                )
            )*/
            ->add('save', 'submit', array('label' => 'Update'));

        // Update Author id
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($currentUser) {
                /** @var User $user */
                $user = $event->getData();
                $extraFields = $user->getExtrafields();
                foreach ($extraFields as $extraField) {
                    $extraField->setAuthor($currentUser);
                }
            }
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Chamilo\UserBundle\Entity\User',
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'user';
    }
}

