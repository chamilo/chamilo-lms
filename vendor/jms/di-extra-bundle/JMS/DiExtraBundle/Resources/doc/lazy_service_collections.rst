Lazy Service Collections
========================
This bundle provides a generic lazy service map/sequence. You can use this when you have a map, or a list of services
where you only need to initialize a few specific services during a single request.

Lazy Service Sequence
---------------------
Often you have a list of services over which you iterate until a service is able to handle your request. In Symfony2, an
example could be the access decision manager which iterates over voters until it finds one that is able to make an a call
on whether access should be granted or not, and then potentially stops. In such a case, any remaining voters, and their
dependencies are not needed, and do not need to be initialized.

The lazy service sequence ensures that initialization is delayed until a service is actually needed. Let's start by
writing the service which consumes the sequence::

    use JMS\DiExtraBundle\Annotation as DI;
    use PhpCollection\Sequence;

    class MyService
    {
        private $voters;

        public function __construct(Sequence $voters)
        {
            $this->voters = $voters;
        }

        public function isGranted(...)
        {
            foreach ($this->voters as $voter) {
                // Do something, potentially return early.
            }
        }
    }

We assume that you would tag your voters in the DIC to also allow other bundles to register services. Then, you can
use the provided ``LazyServiceSequencePass`` which will take care of collecting the tagged services, and generates
the definition for the sequence::

    use JMS\DiExtraBundle\Compiler\LazyServiceSequencePass;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Definition;
    use Symfony\Component\HttpKernel\Bundle\Bundle;

    class MyBundle extends Bundle
    {
        public function build(ContainerBuilder $container)
        {
            $container->addCompilerPass(new LazyServiceSequencePass('voter',
                function(ContainerBuilder $container, Definition $seqDef) {
                    // Add the definition of the sequence as argument for the MyService class.
                    $container->getDefinition('my_service')->addArgument($seqDef);
                }
            ));
        }
    }



Lazy Service Map
----------------
For example, if you have a map of formats to corresponding encoders. Most likely, you only need to load the encoder for
a specific format during a request. Using the map, loading of that encoder would be delayed until you know which format
you need, and not load encoders (and their dependencies) for other formats.

Let's start by writing the service which consumes the map::

    use JMS\DiExtraBundle\Annotation as DI;
    use PhpCollection\Map;

    /** @DI\Service("my_service") */
    class MyService
    {
        private $encoders;

        public function __construct(Map $encoders)
        {
            $this->encoders = $encoders;
        }

        public function useEncoder($format)
        {
            // The encoder is loaded here.
            $encoder = $this->encoders->get('json')->get();
        }
    }

Then, we would add some encoders which we would tag to allow them to be added by other bundles as well::

    /** @DI\Service @DI\Tag("encoder", attributes = {"format": "json"}) */
    class JsonEncoder { }

    /** @DI\Service @DI\Tag("encoder", attributes = {"format": "xml"}) */
    class XmlEncoder { }


Lastly, we just need to add the DI definition of the map which is built by the provided ``LazyServiceMapPass`` as
argument to our service ``my_service``::

    use JMS\DiExtraBundle\Compiler\LazyServiceMapPass;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Definition;
    use Symfony\Component\HttpKernel\Bundle\Bundle;

    class MyBundle extends Bundle
    {
        public function build(ContainerBuilder $container)
        {
            $container->addCompilerPass(new LazyServiceMapPass('encoder', 'format',
                function(ContainerBuilder $container, Definition $mapDef) {
                    // Add the definition of the map as argument for the MyService class.
                    $container->getDefinition('my_service')->addArgument($mapDef);
                }
            ));
        }
    }


