========
Overview
========

This bundle adds AOP capabilities to Symfony2.

If you haven't heard of AOP yet, it basically allows you to separate a
cross-cutting concern (for example, security checks) into a dedicated class,
and not having to repeat that code in all places where it is needed.

In other words, this allows you to execute custom code before, and after the
invocation of certain methods in your service layer, or your controllers. You
can also choose to skip the invocation of the original method, or throw exceptions.

Installation
------------
Checkout a copy of the code::

    git submodule add https://github.com/schmittjoh/JMSAopBundle.git src/JMS/AopBundle

Then register the bundle with your kernel::

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new JMS\AopBundle\JMSAopBundle(),
        // ...
    );

This bundle also requires the CG library for code generation::

    git submodule add https://github.com/schmittjoh/cg-library.git vendor/cg-library

Make sure that you also register the namespaces with the autoloader::

    // app/autoload.php
    $loader->registerNamespaces(array(
        // ...
        'JMS'              => __DIR__.'/../vendor/bundles',
        'CG'               => __DIR__.'/../vendor/cg-library/src',
        // ...
    ));    


Configuration
-------------
::

    jms_aop:
        cache_dir: %kernel.cache_dir%/jms_aop


Usage
-----
In order to execute custom code, you need two classes. First, you need a so-called
pointcut. The purpose of this class is to make a decision whether a method call 
should be intercepted by a certain interceptor. This decision has to be made
statically only on the basis of the method signature itself.

The second class is the interceptor. This class is being called instead
of the original method. It contains the custom code that you would like to
execute. At this point, you have access to the object on which the method is 
called, and all the arguments which were passed to that method.

Examples
--------

1. Logging
~~~~~~~~~~

In this example, we will be implementing logging for all methods that contain
"delete".

Pointcut
^^^^^^^^

::

    <?php
    
    use JMS\AopBundle\Aop\PointcutInterface;
    
    class LoggingPointcut implements PointcutInterface
    {
        public function matchesClass(\ReflectionClass $class)
        {
            return true;
        }

        public function matchesMethod(\ReflectionMethod $method)
        {
            return false !== strpos($method->name, 'delete');
        }
    }

::
    
    # services.yml
    services:
        my_logging_pointcut:
            class: LoggingPointcut
            tags:
                - { name: jms_aop.pointcut, interceptor: logging_interceptor }


LoggingInterceptor
^^^^^^^^^^^^^^^^^^

::

    <?php
    
    use CG\Proxy\MethodInterceptorInterface;
    use CG\Proxy\MethodInvocation;
    use Symfony\Component\HttpKernel\Log\LoggerInterface;
    use Symfony\Component\Security\Core\SecurityContextInterface;
    
    class LoggingInterceptor implements MethodInterceptorInterface
    {
        private $context;
        private $logger;
    
        public function __construct(SecurityContextInterface $context,
                                    LoggerInterface $logger)
        {
            $this->context = $context;
            $this->logger = $logger;
        }
    
        public function intercept(MethodInvocation $invocation)
        {
            $user = $this->context->getToken()->getUsername();
            $this->logger->info(sprintf('User "%s" invoked method "%s".', $user, $invocation->reflection->name));
            
            // make sure to proceed with the invocation otherwise the original
            // method will never be called
            return $invocation->proceed();
        }
    }
    
::

    # services.yml
    services:
        logging_interceptor:
            class: LoggingInterceptor
            arguments: [@security.context, @logger]


2. Transaction Management
~~~~~~~~~~~~~~~~~~~~~~~~~

In this example, we add a @Transactional annotation, and we automatically wrap all methods
where this annotation is declared in a transaction.

Pointcut
^^^^^^^^

::

    use Doctrine\Common\Annotations\Reader;
    use JMS\AopBundle\Aop\PointcutInterface;
    use JMS\DiExtraBundle\Annotation as DI;
    
    /**
     * @DI\Service
     * @DI\Tag("jms_aop.pointcut", attributes = {"interceptor" = "aop.transactional_interceptor"})
     *
     * @author Johannes M. Schmitt <schmittjoh@gmail.com>
     */
    class TransactionalPointcut implements PointcutInterface
    {
        private $reader;
    
        /**
         * @DI\InjectParams({
         *     "reader" = @DI\Inject("annotation_reader"),
         * })
         * @param Reader $reader
         */
        public function __construct(Reader $reader)
        {
            $this->reader = $reader;
        }
    
        public function matchesClass(\ReflectionClass $class)
        {
            return true;
        }
    
        public function matchesMethod(\ReflectionMethod $method)
        {
            return null !== $this->reader->getMethodAnnotation($method, 'Annotation\Transactional');
        }
    }

Interceptor
^^^^^^^^^^^

::
    
    use Symfony\Component\HttpKernel\Log\LoggerInterface;
    use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
    use CG\Proxy\MethodInvocation;
    use CG\Proxy\MethodInterceptorInterface;
    use Doctrine\ORM\EntityManager;
    use JMS\DiExtraBundle\Annotation as DI;
    
    /**
     * @DI\Service("aop.transactional_interceptor")
     *
     * @author Johannes M. Schmitt <schmittjoh@gmail.com>
     */
    class TransactionalInterceptor implements MethodInterceptorInterface
    {
        private $em;
        private $logger;
    
        /**
         * @DI\InjectParams
         * @param EntityManager $em
         */
        public function __construct(EntityManager $em, LoggerInterface $logger)
        {
            $this->em = $em;
            $this->logger = $logger;
        }
    
        public function intercept(MethodInvocation $invocation)
        {
            $this->logger->info('Beginning transaction for method "'.$invocation.'")');
            $this->em->getConnection()->beginTransaction();
            try {
                $rs = $invocation->proceed();
    
                $this->logger->info(sprintf('Comitting transaction for method "%s" (method invocation successful)', $invocation));
                $this->em->getConnection()->commit();
    
                return $rs;
            } catch (\Exception $ex) {
                if ($ex instanceof NotFoundHttpException) {
                    $this->logger->info(sprintf('Committing transaction for method "%s" (exception thrown, but no rollback)', $invocation));
                    $this->em->getConnection()->commit();
                } else {
                    $this->logger->info(sprintf('Rolling back transaction for method "%s" (exception thrown)', $invocation));
                    $this->em->getConnection()->rollBack();
                }
    
                throw $ex;
            }
        }
    }
