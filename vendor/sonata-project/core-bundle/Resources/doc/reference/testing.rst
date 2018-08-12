.. index::
    double: Test Widgets; Definition

Testing
=======

Test Widgets
~~~~~~~~~~~~

You can write unit tests for twig form rendering with the following code.

.. code-block:: php

    use Sonata\CoreBundle\Test\AbstractWidgetTestCase;

    class CustomTest extends AbstractWidgetTestCase
    {
        public function testAcmeWidget()
        {
            $options = array(
                'foo' => 'bar',
            );

            $form     = $this->factory->create('Acme\Form\CustomType', null, $options);
            $html     = $this->renderWidget($form->createView());
            $expected = '<input foo="bar" />';

            $this->assertContains($expected, $this->cleanHtmlWhitespace($html));
        }
    }
