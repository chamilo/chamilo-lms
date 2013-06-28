<?php

namespace ChamiloLMS\Provider;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Routing\Loader\AnnotationFileLoader;
use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Sensio\Bundle\FrameworkExtraBundle\Routing\AnnotatedRouteControllerLoader;

use Doctrine\Common\Annotations\AnnotationReader;

class ReflectionControllerProvider implements ControllerProviderInterface
{
    private $controllerName;

    /**
     * @param string $controllerName
     */
    public function __construct($controllerName)
    {
        $this->controllerName = $controllerName;
    }

    /**
     * @param Application $app
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        /** @var \Silex\ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $reflection = new \ReflectionClass($app[$this->controllerName]);

        $annotationReader = new AnnotationReader();
        //$classAnnotations = $annotationReader->getClassAnnotations($reflection);
        $routeAnnotation = new Route(array());
        $methodAnnotation = new Method(array());

        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $methodName = $method->getName();
            $controllerName = $this->controllerName.':'.$methodName;

            if (in_array($methodName, array('__construct', 'get', 'getManager'))) {
                continue;
            }

            /** @var Route $routeObject */
            $routeObject = $annotationReader->getMethodAnnotation($method, $routeAnnotation);
            $req = $routeObject->getRequirements();
            //$routeObject->setMethods();

            /** @var Method $routeObject */
            $methodObject = $annotationReader->getMethodAnnotation($method, $methodAnnotation);

            $methodsToString = 'GET';
            if ($methodObject) {
                $methodsToString = implode('|', $methodObject->getMethods());
            }

            if ($routeObject) {
                $match = $controllers->match($routeObject->getPath(), $controllerName, $methodsToString);
                //var_dump($controllerName);
                $match->bind($controllerName);
                // setRequirements
                if (!empty($req)) {
                    foreach ($req as $key => $value) {
                        $match->assert($key, $value);
                    }
                }
            }

        }
        return $controllers;
    }
}
