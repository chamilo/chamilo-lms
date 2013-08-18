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

        // Routes are already cached using Flint
        if ($app['debug'] == false) {
            return $controllers;
        }

        $reflection = new \ReflectionClass($app[$this->controllerName]);

        $annotationReader = new AnnotationReader();
        $routeAnnotation = new Route(array());
        $methodAnnotation = new Method(array());

        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $methodName = $method->getName();

            $controllerName = $this->controllerName.':'.$methodName;

            // Parse only function with the "Action" suffix

            if (strpos($methodName, 'Action') === false) {
                continue;
            }

            // Getting all annotations
            $routeObjects = $annotationReader->getMethodAnnotations($method);

            /** @var Method $routeObject */
            $methodObject = $annotationReader->getMethodAnnotation($method, $methodAnnotation);

            $methodsToString = 'GET';

            if ($methodObject) {
                $methodsToString = implode('|', $methodObject->getMethods());
            }

            /** @var Route $routeObject */
            foreach ($routeObjects as $routeObject) {

                if ($routeObject && is_a($routeObject, 'Symfony\Component\Routing\Annotation\Route')) {

                    $match = $controllers->match($routeObject->getPath(), $controllerName, $methodsToString);

                    // setRequirements
                    if (!empty($req)) {
                        foreach ($req as $key => $value) {
                            $match->assert($key, $value);
                        }
                    }
                    $defaults = $routeObject->getDefaults();
                    //var_dump($routeObject);
                    //var_dump($defaults);
                    if (!empty($defaults)) {
                        foreach ($defaults as $key => $value) {
                            $match->value($key, $value);
                        }
                    }

                    $match->bind($controllerName);
                }
            }
        }
        return $controllers;
    }
}
