.. index::
    double: Test Widgets; Definition

Testing
=======

Test Blocks
~~~~~~~~~~~

Given the following block service:

.. code-block:: php

    class CustomBlockService extends AbstractBlockService
    {
        public function execute(BlockContextInterface $blockContext, Response $response = null)
        {
            return $this->renderResponse($blockContext->getTemplate(), array(
                'context' => $blockContext,
                'block' => $blockContext->getBlock(),
                'settings' => $blockContext->getSettings(),
            ), $response);
        }

        public function configureSettings(OptionsResolver $resolver)
        {
            $resolver->setDefaults(array(
                'foo' => 'bar',
                'attr' => array(),
                'template' => false,
            ));
        }
    }


You can write unit tests for block services with the following code.

.. code-block:: php

    use Sonata\BlockBundle\Test\AbstractBlockServiceTestCase;

    class CustomBlockServiceTest extends AbstractBlockServiceTestCase
    {
        public function testDefaultSettings()
        {
            $blockService = new CustomBlockService('foo', $this->templating);
            $blockContext = $this->getBlockContext($blockService);

            $this->assertSettings(array(
                'foo' => bar,
                'attr' => array(),
                'template' => false,
            ), $blockContext);
        }

        public function testExecute()
        {
            $blockService = new CustomBlockService('foo', $this->templating);
            $blockContext = $this->getBlockContext($blockService);

            $service->execute($blockContext);

            $this->assertSame($blockContext, $this->templating->parameters['context']);
            $this->assertInternalType('array', $this->templating->parameters['settings']);
            $this->assertInstanceOf('Sonata\BlockBundle\Model\BlockInterface', $this->templating->parameters['block']);
            $this->assertSame('bar', $this->templating->parameters['foo']);
        }
    }
