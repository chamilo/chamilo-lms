<?php

namespace Bazinga\Bundle\FakerBundle\Tests\DependencyInjection\Compiler;

use Bazinga\Bundle\FakerBundle\Tests\TestCase;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Bazinga\Bundle\FakerBundle\DependencyInjection\Compiler\AddProvidersPass;

class AddProvidersPassTest extends TestCase
{
    public function testProviderIsAdded()
    {
        $targetService = new Definition();
        $targetService->setClass('Faker\Generator');

        $provider = $this->getMock('Acme\Faker\Provider\CustomFakeDataProvider');
        $providerService = new Definition();
        $providerService->setClass(get_class($provider));
        $providerService->addTag('bazinga_faker.provider');

        $builder = new ContainerBuilder();
        $builder->addDefinitions(array(
            'faker.generator' => $targetService,
            'acme.faker.provider.custom' => $providerService,
        ));

        $builder->addCompilerPass(new AddProvidersPass());
        $builder->compile();

        $this->assertNotEmpty($builder->getServiceIds(),
            'The services have been injected.');
        $this->assertNotEmpty($builder->get('faker.generator'),
            'The faker.generator service has been injected.');
        $this->assertNotEmpty($builder->get('acme.faker.provider.custom'),
            'The provider service has been injected.');

        /*
         * Schema:
         *
         * [0] The list of methods.
         *   [0] The name of the method to call.
         *   [1] The arguments to pass into the method call.
         *     [0] First argument to pass into the method call.
         *     ...
         */
        $targetMethodCalls = $builder->getDefinition('faker.generator')->getMethodCalls();
        $this->assertNotEmpty($targetMethodCalls,
            'The faker.generator service got method calls added.');
        $this->assertEquals('addProvider', $targetMethodCalls[0][0],
            'The faker.generator service got a provider added.');
        $this->assertEquals('acme.faker.provider.custom', $targetMethodCalls[0][1][0],
            'The faker.generator service got the correct provider added.');
    }
}
