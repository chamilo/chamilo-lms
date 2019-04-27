Inline Validation
=================

The inline validation is about delegating model validation to a dedicated service.
The current validation implementation built in the Symfony framework is very powerful
as it allows to declare validation on a : class, field and getter. However these declarations
can take a while to code for complex rules. As rules must be a set of a ``Constraint``
and a ``Validator`` instances.

The inline validation tries to provide a nice solution by introducing an ``ErrorElement``
object. The object can be used to check assertions against the model :

.. code-block:: php

    <?php

    $errorElement
        ->with('settings.url')
            ->assertNotNull(array())
            ->assertNotBlank()
        ->end()
        ->with('settings.title')
            ->assertNotNull(array())
            ->assertNotBlank()
            // for minimum length constraint
            ->assertLength(array('min' => 50))
            // for maximum length constraint
            ->assertLength(array('max' => 100))
            ->addViolation('ho yeah!')
        ->end()
    ;

    // ...

    if (/* complex rules */) {
        $errorElement
            ->with('value')
                ->addViolation('Fail to check the complex rules')
            ->end()
        ;
    }

    // ...

    /* conditional validation */
    if ($this->getSubject()->getState() == Post::STATUS_ONLINE) {
        $errorElement
            ->with('enabled')
                ->assertNotNull()
                ->assertTrue()
            ->end()
        ;
    }

.. note::

    This solution relies on the validator component so validation defined through
    the validator component will be used.

.. tip::

    You can also use ``$errorElement->addConstraint(new \Symfony\Component\Validator\Constraints\NotBlank())``
    instead of calling ``assertNotBlank()``.

    You can also use ``$errorElement->addConstraint(new \Symfony\Component\Validator\Constraints\Length(array('min'=>5, 'max'=>100))``
    instead of calling ``assertLength()``.

Using this Validator
--------------------

Add the ``InlineConstraint`` class constraint to your bundle's validation configuration, for example:

.. configuration-block::

    .. code-block:: xml

        <!-- src/Application/Sonata/PageBundle/Resources/config/validation.xml -->

        <?xml version="1.0" ?>
        <constraint-mapping xmlns="http://symfony.com/schema/dic/constraint-mapping"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://symfony.com/schema/dic/constraint-mapping http://symfony.com/schema/dic/constraint-mapping/constraint-mapping-1.0.xsd">

            <class name="Application\Sonata\PageBundle\Entity\Block">
                <constraint name="Sonata\CoreBundle\Validator\Constraints\InlineConstraint">
                    <option name="service">sonata.page.cms.page</option>
                    <option name="method">validateBlock</option>
                </constraint>
            </class>
        </constraint-mapping>

    .. code-block:: yaml

        # src/Application/Sonata/PageBundle/Resources/config/validation.yml

        Application\Sonata\PageBundle\Entity\Block:
            constraints:
                - Sonata\CoreBundle\Validator\Constraints\InlineConstraint:
                    service: sonata.page.cms.page
                    method: validateBlock

There are two important options:

  - ``service``: the service where the validation method is defined
  - ``method``: the service's method to call

.. note::

    If the ``service`` or ``method`` are not string, you will need to re-attach the validation on each request. Set
    the ``serializingWarning`` option to ``true`` once it is done.

The method must accept two arguments:

 - ``ErrorElement``: the instance where assertion can be checked
 - ``value``: the object instance

Example from the ``SonataPageBundle``
-------------------------------------

.. code-block:: php

    <?php

    namespace Sonata\PageBundle\Block;

    use Sonata\PageBundle\Model\PageInterface;
    use Sonata\CoreBundle\Validator\ErrorElement;
    use Sonata\BlockBundle\Block\BaseBlockService;
    use Sonata\BlockBundle\Model\BlockInterface;

    class RssBlockService extends BaseBlockService
    {
        // ...

        public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
        {
            $errorElement
                ->with('settings.url')
                    ->assertNotNull(array())
                    ->assertNotBlank()
                ->end()
                ->with('settings.title')
                    ->assertNotNull(array())
                    ->assertNotBlank()

                    // for minimum length constraint
                    ->assertLength(array('min' => 50))

                    // for maximum length constraint
                    ->assertLength(array('max' => 100))
                    ->addViolation('ho yeah!')
                ->end()
            ;
        }

        // ...
    }
